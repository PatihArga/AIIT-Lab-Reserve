# Plan: Calendar Availability Fix & Booking UX Improvement

**Date:** 2026-05-18  
**Branch:** `CoreBookingBackEnd`  
**Status:** Pending Implementation

---

## 1. Problem Statement

### Bug 1 — Calendar Slot Shows "TERPESAN" After Partial Booking

**Symptom:** When a user reserves one computer (e.g., PC-2) for 11:00–12:00, the
dashboard calendar immediately marks that entire time slot as **"TERPESAN"** (blocked,
non-clickable). Eight other computers are still free, but the UI makes the slot look
fully occupied.

**Expected:** A slot is only marked "TERPESAN" when it is truly full — meaning either a
`full_room` booking exists for that slot, or all online computers are taken. Partial
`computers_only` bookings should leave the slot clickable so that other PCs can still
be reserved.

---

### Bug 2 — Computer Selection Has No Slot-Aware Availability Info

**Symptom:** On the booking/schedule page, the computer grid is static: it shows each
unit's hardware status (online / maintenance) but does NOT reflect which PCs are already
booked for the chosen date + time. Users discover collisions only after the availability
banner fires—or worse, at the final submission step.

**Expected:** Once the user has selected a date AND start/end time, the computer grid
should automatically load and display per-PC slot availability (Available / Terpakai /
Maintenance), letting the user pick only from genuinely free units.

---

## 2. Root Cause Analysis

### 2.1 Calendar Bug Root Cause

**File:** `app/Http/Controllers/BookingController.php`, `dashboard()` method (lines 64–77)

```php
$calendarEvents = $user->bookings()               // ← only the current user's bookings
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date',  now()->year)
    ->get(['date', 'start_time', 'end_time'])      // ← booking_type NOT selected
    ->groupBy(fn ($b) => (int) $b->date->day)
    ->map(fn ($group) => $group
        ->flatMap(function ($b) {
            $start = (int) Carbon::parse($b->start_time)->hour;
            $end   = (int) Carbon::parse($b->end_time)->hour;
            return range($start, max($start, $end - 1));
        })
        ->unique()->sort()->values()
    );
```

Two compounding flaws:

1. **Wrong scope:** It queries the *current user's* bookings. The calendar is meant to
   help the user plan new bookings. "Terpesan" should reflect lab-wide unavailability,
   not whether this user already has a booking there.

2. **No booking-type distinction:** Every booking type (including `computers_only` with
   one PC) is treated equally. There is no logic to distinguish "partially occupied" from
   "fully blocked".

**File:** `resources/views/dashboard.blade.php`, JS function `renderTimeSlots()` (line 541)

```js
const booked = RESERVATIONS[day] && RESERVATIONS[day].includes(Math.floor(slot.startHour));
```

The JS simply checks membership in the booked-hours array and applies `.slot-booked`
(non-clickable, shows "Terpesan"). There is no concept of a "partial" state.

---

### 2.2 Booking UX Root Cause

**File:** `resources/views/booking/schedule.blade.php`

The computer grid is rendered server-side by `<x-computer-grid>` with static data from
`Computer::orderBy('unit_number')->get(...)`. It shows hardware status only.

The availability check runs via `runAvailabilityCheck()` (Alpine.js, lines 350–377) and
tells the user whether the slot is free, but it does NOT update the visual state of
individual PC cards.

The existing `/api/computers/available` endpoint (used correctly by the dashboard modal)
already returns per-PC availability — it is simply not being called from the schedule
page.

---

## 3. Fix Plan

### Fix A — Calendar: Lab-Wide Slot Classification

**Goal:** Replace the single `$calendarEvents` array (user's booked hours) with two
separate datasets:

| Variable | What it means | Calendar effect |
|---|---|---|
| `$fullBlocks` | Hours where the lab is truly unavailable (full_room OR all PCs booked) | `slot-booked` — non-clickable "Terpesan" |
| `$userEvents` | Hours where the current user already has a booking | Dot indicator on calendar day |

**Logic for computing `$fullBlocks`:**

```
For each hour H in the current month:
  If any approved/submitted/under_review full_room booking overlaps H → FULL BLOCK
  Else count how many distinct online PCs are booked for H across all computers_only bookings
    If count >= total_online_computers → FULL BLOCK
  Otherwise → PARTIAL (slot remains clickable; modal will show real per-PC status)
```

Because this logic is per-hour across the whole month, computing it naively in PHP
(one query per hour × 14 hours × 31 days) would be ~430 queries. We avoid this by:

1. Fetching the month's relevant bookings in **two** queries: one for
   `full_room`/`room_only` bookings, one for `computers_only` with their pivot data.
2. Computing the block state in a PHP collection pass.

#### Step A-1: Update `BookingController::dashboard()`

Replace the `$calendarEvents` block with:

```php
// All active bookings for the current month (lab-wide, not user-specific)
$monthBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date',  now()->year)
    ->get(['id', 'date', 'start_time', 'end_time', 'booking_type']);

$totalOnline = Computer::where('status', 'online')->count();

// fullBlocks: day -> [hours that are truly unavailable]
$fullBlocks = $monthBookings
    ->groupBy(fn ($b) => (int) $b->date->day)
    ->map(function ($dayBookings) use ($totalOnline) {
        $blockedHours = [];
        // Collect hour ranges for each booking
        $hourRanges = $dayBookings->map(function ($b) {
            $start = (int) Carbon::parse($b->start_time)->hour;
            $end   = (int) Carbon::parse($b->end_time)->hour;
            return [
                'hours'        => range($start, max($start, $end - 1)),
                'booking_type' => $b->booking_type,
                'computer_ids' => $b->computers->pluck('id')->toArray(),
            ];
        });

        // Gather every unique hour in this day
        $allHours = $hourRanges->flatMap(fn ($r) => $r['hours'])->unique();

        foreach ($allHours as $hour) {
            // Any full_room booking touching this hour → full block
            $hasFullRoom = $hourRanges->contains(
                fn ($r) => $r['booking_type'] === 'full_room' && in_array($hour, $r['hours'])
            );
            if ($hasFullRoom) {
                $blockedHours[] = $hour;
                continue;
            }
            // Count distinct PCs booked in this hour
            $bookedPcIds = $hourRanges
                ->filter(fn ($r) => $r['booking_type'] === 'computers_only' && in_array($hour, $r['hours']))
                ->flatMap(fn ($r) => $r['computer_ids'])
                ->unique();
            if ($bookedPcIds->count() >= $totalOnline && $totalOnline > 0) {
                $blockedHours[] = $hour;
            }
        }

        return collect($blockedHours)->unique()->sort()->values();
    });

// userEvents: day -> [hours where this user has any booking]
$userEvents = $user->bookings()
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date',  now()->year)
    ->get(['date', 'start_time', 'end_time'])
    ->groupBy(fn ($b) => (int) $b->date->day)
    ->map(fn ($group) => $group
        ->flatMap(function ($b) {
            $start = (int) Carbon::parse($b->start_time)->hour;
            $end   = (int) Carbon::parse($b->end_time)->hour;
            return range($start, max($start, $end - 1));
        })
        ->unique()->sort()->values()
    );
```

Pass both to the view:
```php
return view('dashboard', compact(
    'upcomingBookings', 'completedBookings', 'stats',
    'fullBlocks', 'userEvents'   // replaces 'calendarEvents'
));
```

#### Step A-2: Update `dashboard.blade.php` — JS constants

Replace:
```js
const RESERVATIONS = @json($calendarEvents);
```

With:
```js
const FULL_BLOCKS  = @json($fullBlocks);   // day -> [fully-blocked hours]
const USER_EVENTS  = @json($userEvents);   // day -> [hours user has booked]
```

#### Step A-3: Update `renderCalendar()` — dot indicator

Change `hasResv` to use `USER_EVENTS`:
```js
const hasResv = onCurrentMonth && USER_EVENTS[d] && USER_EVENTS[d].length > 0;
```

Update the event-count badge:
```js
const resvCount = onCurrentMonth ? Object.keys(USER_EVENTS).length : 0;
```

#### Step A-4: Update `renderTimeSlots()` — slot state

Change the booked check:
```js
// Before
const booked = RESERVATIONS[day] && RESERVATIONS[day].includes(Math.floor(slot.startHour));

// After
const fullyBlocked = FULL_BLOCKS[day] && FULL_BLOCKS[day].includes(Math.floor(slot.startHour));
const hasUserEvent = USER_EVENTS[day]  && USER_EVENTS[day].includes(Math.floor(slot.startHour));
```

Update slot element rendering:
```js
el.className = 'cal-slot' + (fullyBlocked ? ' slot-booked' : '');

// Status label: show "Saya" indicator if user has booking here
const statusLabel = fullyBlocked
    ? 'Penuh'
    : hasUserEvent
        ? 'Saya'
        : 'Tersedia';
```

Only disable click on fully-blocked slots:
```js
if (!fullyBlocked) el.addEventListener('click', () => openSlotModal(day, slot));
```

#### Step A-5: Add a "Saya" dot style (CSS)

```css
.cal-slot.slot-mine {
    border-color: #DBEAFE;
    background: #EFF6FF;
}
.cal-slot.slot-mine .slot-status-text {
    color: #3B82F6;
}
```

Apply it:
```js
el.className = 'cal-slot'
    + (fullyBlocked ? ' slot-booked' : '')
    + (!fullyBlocked && hasUserEvent ? ' slot-mine' : '');
```

---

### Fix B — Schedule Page: Dynamic Per-PC Availability Grid

**Goal:** When the user has chosen a date + start time + end time on the booking/schedule
page, automatically call `/api/computers/available` and update each PC card to show its
real availability for that exact slot.

#### Step B-1: Alpine.js data additions

In the `bookingForm()` function, add:
```js
pcAvailability: [],         // array from /api/computers/available
pcLoadingState: 'idle',     // idle | loading | loaded | error
pcTimer: null,
```

Add a watcher for the three time-related fields in `init()`:
```js
['isoDate', 'startTime', 'endTime'].forEach(prop => {
    this.$watch(prop, () => this.schedulePcAvailabilityLoad());
});
```

Add the loader method:
```js
schedulePcAvailabilityLoad() {
    if (!this.isoDate || !this.startTime || !this.endTime) {
        this.pcAvailability = [];
        this.pcLoadingState = 'idle';
        return;
    }
    if (this.startTime >= this.endTime) return;
    if (this.pcTimer) clearTimeout(this.pcTimer);
    this.pcTimer = setTimeout(() => this.loadPcAvailability(), 300);
},

async loadPcAvailability() {
    this.pcLoadingState = 'loading';
    const params = new URLSearchParams({
        date: this.isoDate,
        start_time: this.startTime,
        end_time: this.endTime,
    });
    try {
        const res = await fetch(
            @json(route('api.availability.computers')) + '?' + params.toString(),
            { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin' }
        );
        if (!res.ok) throw new Error('http ' + res.status);
        const data = await res.json();
        this.pcAvailability = data.computers;
        this.pcLoadingState = 'loaded';
        // Deselect any currently-checked PCs that are no longer available
        this.$nextTick(() => {
            this.$el.querySelectorAll('input[name="computers[]"]:checked').forEach(cb => {
                const pcId = parseInt(cb.value, 10);
                const pc = this.pcAvailability.find(p => p.id === pcId);
                if (pc && !pc.available) cb.checked = false;
            });
            this.scheduleAvailabilityCheck();
        });
    } catch (e) {
        this.pcLoadingState = 'error';
    }
},

getPcState(pcId) {
    if (this.pcLoadingState !== 'loaded' || !this.pcAvailability.length) return null;
    return this.pcAvailability.find(p => p.id === pcId) || null;
},
```

#### Step B-2: Update `<x-computer-grid>` component or inline markup

The existing `<x-computer-grid>` renders static HTML with no Alpine bindings. Replace the
section with a dynamic version driven by the `pcAvailability` state.

In `booking/schedule.blade.php`, replace the inner content of the "Pilih Unit Komputer"
section:

```blade
<div x-show="selected !== 'room_only'" x-transition>
    <x-section label="Pilih Unit Komputer">

        {{-- Loading / idle hint --}}
        <div class="mb-4">
            <template x-if="pcLoadingState === 'idle'">
                <p class="text-sm text-ink-700/60">
                    Pilih tanggal dan waktu terlebih dahulu untuk melihat ketersediaan unit.
                </p>
            </template>
            <template x-if="pcLoadingState === 'loading'">
                <p class="text-sm text-ink-700/60 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8"/>
                    </svg>
                    Memuat ketersediaan unit…
                </p>
            </template>
            <template x-if="pcLoadingState === 'loaded'">
                <p class="text-sm text-ink-700/60">
                    Pilih unit yang tersedia. Unit <span class="font-semibold text-amber-600">Terpakai</span>
                    sudah dipesan untuk slot ini.
                </p>
            </template>
            <template x-if="pcLoadingState === 'error'">
                <p class="text-sm text-red-600">Gagal memuat ketersediaan unit. Coba pilih ulang waktu.</p>
            </template>
        </div>

        {{-- Computer grid --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach ($computers as $computer)
            @php $pcId = $computer->id; @endphp
            <div x-data="{ pcId: {{ $pcId }} }"
                 :class="{
                     'opacity-50 cursor-not-allowed': getPcState(pcId) && !getPcState(pcId).available,
                     'ring-2 ring-ink-700': $el.querySelector('input')?.checked,
                 }"
                 class="relative border rounded-xl p-3 text-center transition-all">

                {{-- Status badge overlay when slot data is loaded --}}
                <template x-if="getPcState(pcId) && !getPcState(pcId).available">
                    <span class="absolute top-1.5 right-1.5 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full"
                          :class="getPcState(pcId).status !== 'online'
                              ? 'bg-ink-50 text-ink-700/50'
                              : 'bg-amber-100 text-amber-700'">
                        <span x-text="getPcState(pcId).status !== 'online' ? 'Maintenance' : 'Terpakai'"></span>
                    </span>
                </template>
                <template x-if="getPcState(pcId) && getPcState(pcId).available">
                    <span class="absolute top-1.5 right-1.5 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                        Tersedia
                    </span>
                </template>

                <label class="flex flex-col items-center gap-2 cursor-pointer"
                       :class="{ 'cursor-not-allowed': getPcState(pcId) && !getPcState(pcId).available }">
                    <input type="checkbox"
                           name="computers[]"
                           value="{{ $computer->id }}"
                           class="sr-only"
                           {{ in_array($computer->id, $draft['computers'] ?? []) ? 'checked' : '' }}
                           :disabled="(getPcState(pcId) && !getPcState(pcId).available) || '{{ $computer->status }}' !== 'online'"
                           @change="scheduleAvailabilityCheck()">

                    {{-- Monitor icon --}}
                    <div class="w-8 h-6 rounded border-2 flex items-center justify-center transition-all"
                         :class="getPcState(pcId)
                             ? (getPcState(pcId).available ? 'border-emerald-400 bg-emerald-50' : 'border-ink-200 bg-ink-50')
                             : '{{ $computer->status === "online" ? "border-ink-300 bg-ink-50" : "border-ink-200 bg-ink-100 opacity-60" }}'">
                    </div>

                    <span class="text-xs font-mono font-bold text-ink-900">{{ $computer->label }}</span>
                </label>
            </div>
            @endforeach
        </div>

        <p class="form-hint mt-3">Pilih minimal 1 unit. Unit yang Terpakai tidak dapat dipilih.</p>
    </x-section>
</div>
```

> **Note:** If the `<x-computer-grid>` Blade component is reused elsewhere, create the
> dynamic version inline on the schedule page only. The component itself stays unchanged
> for the admin computer-management view.

#### Step B-3: Deselect unavailable computers when slot changes

Already handled in `loadPcAvailability()` via the `$nextTick` block above — it unchecks
any checked PC whose slot state is `available: false`.

---

## 4. Files to Change

| File | Change |
|---|---|
| `app/Http/Controllers/BookingController.php` | Replace `$calendarEvents` computation with `$fullBlocks` + `$userEvents` |
| `resources/views/dashboard.blade.php` | Update JS constants, `renderCalendar()`, `renderTimeSlots()`, slot CSS |
| `resources/views/booking/schedule.blade.php` | Add `pcAvailability` state, `loadPcAvailability()`, replace static grid with dynamic version |

No new routes, migrations, or models are needed.

---

## 5. Step-by-Step Implementation Order

```
Step 1  BookingController::dashboard()
        - Remove old $calendarEvents query
        - Add $monthBookings query (lab-wide, with computers eager-load)
        - Compute $fullBlocks collection (PHP)
        - Compute $userEvents collection (from $user->bookings)
        - Pass both to view

Step 2  dashboard.blade.php — JS constants
        - Replace `const RESERVATIONS` with `const FULL_BLOCKS` + `const USER_EVENTS`
        - Update all references from RESERVATIONS → FULL_BLOCKS or USER_EVENTS

Step 3  dashboard.blade.php — renderCalendar()
        - Change hasResv to use USER_EVENTS

Step 4  dashboard.blade.php — renderTimeSlots()
        - Change `booked` → `fullyBlocked` (uses FULL_BLOCKS)
        - Add `hasUserEvent` (uses USER_EVENTS)
        - Update el.className logic (slot-booked, slot-mine)
        - Update click handler: only block click on fullyBlocked
        - Update status label text

Step 5  dashboard.blade.php — CSS
        - Add `.cal-slot.slot-mine` style

Step 6  booking/schedule.blade.php — Alpine state
        - Add `pcAvailability`, `pcLoadingState`, `pcTimer`
        - Add watchers for isoDate, startTime, endTime → schedulePcAvailabilityLoad()
        - Add `schedulePcAvailabilityLoad()`, `loadPcAvailability()`, `getPcState()`

Step 7  booking/schedule.blade.php — computer grid markup
        - Replace <x-computer-grid> section with dynamic Alpine-driven grid
        - Bind :disabled, :class, status badge per PC based on getPcState()
        - Wire @change to scheduleAvailabilityCheck()
```

---

## 6. Edge Cases & Rules

### Calendar edge cases
- **No online computers:** If `$totalOnline === 0`, never mark a slot as fully blocked
  based on computer count (the condition `>= $totalOnline && $totalOnline > 0` handles this).
- **Viewing a different month:** `FULL_BLOCKS` only holds data for the current real month.
  When navigating to a different month via `calNav()`, no blocks exist (no server data).
  This is acceptable — the user can still open the slot modal which fetches live data.
- **`room_only` bookings:** These don't occupy specific computers, so they don't
  contribute to the PC-count blocking logic. They only block new `full_room` or exclusive
  `room_only` bookings (already handled by `BookingService::checkConflict`). Room-only
  bookings are NOT counted toward `$fullBlocks` based on PC count — they leave computer
  slots open.

### Schedule page edge cases
- **Draft repopulation on validation error:** After a failed submit, `$draft` may contain
  computer IDs that are now unavailable. The `$nextTick` deselect logic in
  `loadPcAvailability()` handles this on load.
- **`full_room` type selected:** `pcLoadingState` check is inside
  `x-show="selected !== 'room_only'"` — if type is `full_room`, the grid is still shown
  but the user can't deselect computers (all are implicitly included). Consider showing an
  informational message instead of a selectable grid for `full_room`.
- **API error:** `pcLoadingState = 'error'` shows an error message; existing selection
  remains intact; `scheduleAvailabilityCheck()` still fires normally.
- **Time changes after PC selection:** `schedulePcAvailabilityLoad()` debounces 300ms and
  deselects unavailable PCs, then re-triggers the availability check banner.

---

## 7. Testing Checklist

### Calendar fix
- [ ] Book PC-2 for a slot. Calendar shows that slot with `slot-mine` class (blue tint),
      NOT `slot-booked`. Slot is still clickable.
- [ ] Open the slot modal after booking PC-2. PC-2 shows as "Terpakai"; PC-1, PC-3–9 show
      as "Tersedia".
- [ ] Create a `full_room` booking for another slot. That slot shows as `slot-booked`
      (amber, non-clickable).
- [ ] With 9 computers online, create `computers_only` bookings for all 9 PCs in one slot.
      That slot shows as `slot-booked`.
- [ ] User has no bookings on a day → no dot on the calendar day.
- [ ] User has a booking → dot appears on the calendar day.
- [ ] Navigate to a different month → no false "FULL BLOCK" slots appear (FULL_BLOCKS is
      empty for months other than the current real month).

### Schedule page fix
- [ ] Select type = `computers_only`, no date/time yet → grid shows static hardware status
      (pcLoadingState = idle), hint text is shown.
- [ ] Select date + time → spinner appears → per-PC status loads. PCs booked for that slot
      show as "Terpakai" (disabled). Available PCs show as "Tersedia".
- [ ] PC previously checked that becomes "Terpakai" after time change → auto-unchecked.
- [ ] Change start_time → API re-fetches with 300ms debounce.
- [ ] API failure → error message shown; user can retry by re-selecting time.
- [ ] Select type = `room_only` → computer grid hidden (existing behavior preserved).
- [ ] Select type = `full_room` → computer grid visible; all PCs reflect slot availability.

---

## 8. Out of Scope

- Paginating or caching `$monthBookings` — the total number of rows per month is small
  (bounded by the lab's 14-hour operating window × 31 days × small user base).
- Multi-month calendar pre-fetching — users navigate months infrequently.
- Real-time WebSocket updates to the calendar — polling/refresh on each click is sufficient.
