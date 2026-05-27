# PLAN: Block Incompatible Room Types When Computers Are Already Booked

**Date:** 26 May 2026  
**Status:** DRAFT — awaiting review  
**Branch:** `CoreBookingBackEnd`  
**Edge case label:** EC-H (next after the auth rework EC-G logged in `PROJECT-HANDOVER.md`)

> **Why this is a rewrite:** an earlier draft in this same file disabled the entire "Ruang Saja" card in the dashboard modal when computers were booked, forcing users to go through the full schedule page to pick `room_only (shared)`. That contradicts the user's brief ("leave the room only (sharing) option open — both on the dashboard calendar and on the request page"). The dashboard modal **does** have a sharing sub-row (`modal-sharing-row` with `Eksklusif` / `Berbagi` buttons), so we can be more precise. This rewrite disables only the incompatible sub-option (`Eksklusif`) while keeping `Berbagi` selectable everywhere.

---

## Problem Statement

When a user (or another user) has already reserved one or more computers (`computers_only`) at a given time slot, the UI still lets a second user pick:

- **Ruang + Komputer** (`full_room`) — claims the entire room + every PC
- **Ruang Saja → Eksklusif** (`room_only` + `exclusive`) — claims the entire room exclusively

Both are incompatible with an existing `computers_only` booking. The backend (`BookingService::checkConflict`) correctly rejects them at submit time, but the UI never warns the user — they fill out the entire form before getting a generic conflict error at submit.

### What should happen

| Type | When `computers_only` already booked in slot |
|---|---|
| `computers_only` | ✅ Allowed — book remaining PCs |
| `full_room` | ❌ Blocked — would evict the computer user |
| `room_only` + `exclusive` | ❌ Blocked — would evict the computer user |
| `room_only` + `shared` | ✅ Allowed — shared room coexists with PCs |

The "shared" option stays **fully selectable** in both:
1. The **dashboard slot modal** (pick "Ruang Saja" → only "Berbagi" sub-button is active)
2. The **booking schedule page** (pick "Ruang Saja" radio → only "Berbagi" sub-radio is active)

---

## Current Behavior — Where the Gap Is

### ✅ Backend (no changes needed)

`BookingService::checkConflict()` already handles all three cases correctly:

| Line | Check |
|---|---|
| `app/Services/BookingService.php:67-69` | `full_room` conflicts with any active booking → blocks against existing `computers_only` |
| `app/Services/BookingService.php:96-104` | `room_only + exclusive` explicitly checks for existing `computers_only` and returns `true` |
| `app/Services/BookingService.php:107-112` | `room_only + shared` only conflicts with `room_only + exclusive` — correctly compatible with `computers_only` |

### ❌ Dashboard modal — only reacts to `sharedRoom`

`dashboard.blade.php:658-662`:

```js
document.querySelectorAll('.type-card').forEach(c => {
    c.classList.remove('active', 'is-disabled');
    const t = c.getAttribute('data-type');
    if (sharedRoom && (t === 'both' || t === 'room')) c.classList.add('is-disabled');
});
```

No `computerBooked` concept exists. When computers are booked, all three type cards are enabled.

### ❌ Schedule page — only reacts to `$sharedRoomActive`

`schedule.blade.php:50, 100, 127`:

```blade
@php $sharedRoomActive = $draft['shared_room_active'] ?? false; @endphp
...
<label class="block {{ $sharedRoomActive ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'cursor-pointer' }}">
```

No equivalent flag for "computers are already booked". The user can pick `full_room` and only get a conflict error after pressing submit.

### ✅ Availability API — has the data we need

`AvailabilityController::availableComputers()` already queries all active bookings for the requested slot. Adding a `has_computer_bookings` boolean to the JSON response is a one-line additive change.

---

## Proposed Fix

### Symmetric to the shared-room fix (EC-A/EC-C/EC-D)

We mirror the established pattern:

| Concept | Shared-room fix (EC-A) | Computer-booked fix (new) |
|---|---|---|
| Controller var | `$sharedRoomBlocks` | `$computerBookedBlocks` |
| JS constant | `SHARED_ROOM_BLOCKS` | `COMPUTER_BOOKED_BLOCKS` |
| Per-slot flag | `sharedRoom` / `sharedRoomModal` | `computerBooked` |
| Session draft key | `shared_room_active` | `computer_booked_active` |
| URL param | `?room_shared=1` | `?computer_booked=1` |
| API flag | (n/a — full block) | `has_computer_bookings` on `availableComputers()` |
| Calendar visual | New teal "Berbagi" state | **None** — slot stays "Tersedia" / "Saya" / "Menunggu" |

### Why no new calendar visual?

Unlike `room_only + shared` (which marks the whole room as in-use), a `computers_only` booking only takes specific PCs. The slot is still partially available — different PCs can be booked AND `room_only + shared` is fine. A new color would imply scarcity that doesn't really exist. The restriction is type-only, surfaced inside the modal and on the schedule page, not on the calendar tile.

### Modal sharing-row precision (the key difference from the previous draft)

The dashboard modal has a `modal-sharing-row` that appears when "Ruang Saja" is selected, containing two buttons:
- `Eksklusif` (`data-val="exclusive"`)
- `Berbagi` (`data-val="shared"`)

We disable only the `Eksklusif` button when `computerBooked` is true, leaving `Berbagi` selectable. This matches the user's brief exactly — "Ruang Saja (Berbagi)" stays open everywhere.

---

## Files to Change

| # | File | What changes |
|---|---|---|
| 1 | `app/Http/Controllers/Api/AvailabilityController.php` | Add `has_computer_bookings` to `availableComputers()` response |
| 2 | `app/Http/Controllers/BookingController.php` | Compute `$computerBookedBlocks` (mirroring `$sharedRoomBlocks`); add `computer_booked_active` to `$prefill` |
| 3 | `resources/views/dashboard.blade.php` | (a) expose `COMPUTER_BOOKED_BLOCKS` to JS; (b) compute `computerBooked` per slot; (c) pass it through `openSlotModal()`; (d) disable `full_room` card + `exclusive` sub-button when `computerBooked`; (e) add amber info banner; (f) carry `computer_booked=1` in `navigateToBooking()` |
| 4 | `resources/views/booking/schedule.blade.php` | (a) read `$computerBookedActive` from draft; (b) static banner when set; (c) disable `full_room` radio when set; (d) disable `exclusive` sub-radio (NOT the `room_only` card) when set; (e) Alpine `applyTypeRestrictions()` re-applies dynamically when API returns `has_computer_bookings: true` |

**No new files. No migration. No model change. No change to `BookingService`, `BookingStoreRequest`, admin flow, or auth.**

---

## Detailed Changes

### 1. `AvailabilityController::availableComputers()` — add API flag

After computing the active bookings for the slot, add:

```php
$hasComputerBookings = $activeBookings
    ->where('booking_type', 'computers_only')
    ->isNotEmpty();
```

Append to the JSON response:

```php
return response()->json([
    'computers'             => $computers,
    'has_computer_bookings' => $hasComputerBookings,
]);
```

> **Backwards compat:** existing consumers of this endpoint only read `computers`. Adding a new key is additive and cannot break them.

### 2. `BookingController::dashboard()` — compute `$computerBookedBlocks`

Insert **after** the existing `$sharedRoomBlocks` block (around line 139):

```php
// Slots where any active computers_only booking exists.
// Mirrors $sharedRoomBlocks but tracks the OTHER incompatibility direction:
// when computers_only is active, full_room and room_only+exclusive are blocked,
// but computers_only and room_only+shared remain available.
$computerBookedBlocks = $monthBookings
    ->filter(fn ($b) => $b->booking_type === 'computers_only')
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

Update `compact()` to include `'computerBookedBlocks'`.

### 2b. `BookingController::showSchedule()` — accept `?computer_booked=1`

In the prefill block, add:

```php
'computer_booked_active' => $request->boolean('computer_booked'),
```

(Alongside the existing `'shared_room_active' => $request->boolean('room_shared')`.)

### 3. `dashboard.blade.php` — six small changes

#### 3a. Expose new data to JS (next to `SHARED_ROOM_BLOCKS`)

```js
const COMPUTER_BOOKED_BLOCKS = @json($computerBookedBlocks);
```

#### 3b. Persist the per-modal flag (next to `currentSlotIsSharedRoom`)

```js
let currentSlotIsComputerBooked = false;
```

#### 3c. Compute `computerBooked` per slot in `renderTimeSlots()` (next to the `sharedRoomModal` computation)

```js
const computerBooked = !hardBlocked
                     && COMPUTER_BOOKED_BLOCKS[day]
                     && COMPUTER_BOOKED_BLOCKS[day].includes(hourKey);
```

Pass it into the modal opener:

```js
if (!hardBlocked) el.addEventListener('click', () => openSlotModal(day, slot, {
    softPending, isMine, sharedRoom: sharedRoomModal, computerBooked
}));
```

> **No visual class change for the slot itself** — `computerBooked` does not add a CSS class. The slot keeps whatever class it already had (`slot-mine`, `slot-shared`, `slot-pending`, or plain `cal-slot`). The restriction surfaces inside the modal only.

#### 3d. `openSlotModal()` — disable the incompatible options

Replace the existing type-card disabling block with:

```js
const sharedRoom    = !!(opts && opts.sharedRoom);
const computerBooked = !!(opts && opts.computerBooked);
currentSlotIsSharedRoom    = sharedRoom;
currentSlotIsComputerBooked = computerBooked;

// Type cards
document.querySelectorAll('.type-card').forEach(c => {
    c.classList.remove('active', 'is-disabled');
    const t = c.getAttribute('data-type');
    if (sharedRoom && (t === 'both' || t === 'room')) {
        c.classList.add('is-disabled');           // sharedRoom: entire 'room' card off
    } else if (computerBooked && t === 'both') {
        c.classList.add('is-disabled');           // computerBooked: only 'both' off; 'room' stays
    }
});

// Sharing sub-buttons (visible only when 'Ruang Saja' is selected)
document.querySelectorAll('.sharing-btn').forEach(b => {
    b.classList.remove('is-disabled');
    const v = b.getAttribute('data-val');
    if (computerBooked && v === 'exclusive') {
        b.classList.add('is-disabled');           // only 'Berbagi' remains active
    }
});

// If 'Eksklusif' is currently the active sharing choice and is being disabled, switch to 'Berbagi'
if (computerBooked && currentSharing === 'exclusive') {
    const shared = document.querySelector('.sharing-btn[data-val="shared"]');
    if (shared) {
        document.querySelectorAll('.sharing-btn').forEach(o => o.classList.remove('active'));
        shared.classList.add('active');
        currentSharing = 'shared';
    }
}
```

The existing `selectResType()` and `selectSharing()` already early-return when the clicked element has `.is-disabled`, so no further click-handler changes are needed.

#### 3e. Info banner — show one of three, depending on context

Replace the existing single sharedRoom banner with a small helper that picks the right message:

```js
let banner = document.getElementById('modal-restriction-banner');
let msg = null;

if (sharedRoom) {
    msg = '<b>Ruangan sedang digunakan berbagi</b> pada slot ini. Anda tetap dapat memesan <b>Komputer</b>.';
} else if (computerBooked) {
    msg = '<b>Ada reservasi komputer aktif</b> pada slot ini. Opsi <b>Ruang + Komputer</b> dan <b>Ruang Saja → Eksklusif</b> dinonaktifkan. <b>Komputer</b> dan <b>Ruang Saja → Berbagi</b> tetap tersedia.';
}

if (msg) {
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'modal-restriction-banner';
        banner.style.cssText = 'display:flex;gap:8px;align-items:flex-start;padding:10px 12px;border-radius:8px;margin-bottom:14px;font-size:11.5px;line-height:1.45;';
        const section = document.getElementById('modal-computers-section');
        section.parentNode.insertBefore(banner, section);
    }
    if (sharedRoom) {
        banner.style.background = '#F0FDFA'; banner.style.borderColor = '#99F6E4';
        banner.style.color = '#0D9488';      banner.style.border = '1px solid #99F6E4';
    } else {
        banner.style.background = '#FEF3C7'; banner.style.borderColor = '#FDE68A';
        banner.style.color = '#92400E';      banner.style.border = '1px solid #FDE68A';
    }
    banner.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span>' + msg + '</span>';
    banner.style.display = 'flex';
} else if (banner) {
    banner.style.display = 'none';
}
```

> If `sharedRoom` AND `computerBooked` are both true, the sharedRoom banner wins (it's the stricter restriction — only `computers_only` is left). Existing behavior preserved.

#### 3f. `navigateToBooking()` — carry both restriction flags

```js
if (currentSlotIsSharedRoom)    params.set('room_shared', '1');
if (currentSlotIsComputerBooked) params.set('computer_booked', '1');
```

### 4. `booking/schedule.blade.php` — four changes

#### 4a. Read the new flag (next to `$sharedRoomActive`, line 50)

```blade
@php $computerBookedActive = $draft['computer_booked_active'] ?? false; @endphp
```

#### 4b. Static info banner (after the existing sharedRoom banner)

```blade
@if ($computerBookedActive && !$sharedRoomActive)
    <div class="mb-6 flex items-start gap-3 p-4 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="16" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>Ada reservasi komputer aktif pada slot ini — opsi <strong>Ruang + Komputer</strong> dan <strong>Ruang Saja (Eksklusif)</strong> dinonaktifkan. <strong>Komputer Saja</strong> dan <strong>Ruang Saja (Berbagi)</strong> tetap tersedia.</span>
    </div>
@endif
```

#### 4c. Disable `full_room` radio — combined condition

`schedule.blade.php:100` change:

```blade
<label class="block {{ ($sharedRoomActive || $computerBookedActive) ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'cursor-pointer' }}">
    <input type="radio" name="type" value="full_room" class="sr-only peer" x-model="selected"
           {{ ($sharedRoomActive || $computerBookedActive) ? 'disabled' : '' }}>
```

#### 4d. `room_only` card stays enabled when `computerBookedActive`; only the `exclusive` sub-radio is disabled

`schedule.blade.php:127` stays driven by `$sharedRoomActive` only (do **not** add `$computerBookedActive` to the room_only card's disable condition).

Inside the `room_only` card, locate the sharing sub-options (exclusive / shared radios). For the `exclusive` radio, add the new condition:

```blade
{{-- Inside the existing exclusive sub-option markup --}}
<label class="... {{ $computerBookedActive ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' }}">
    <input type="radio" name="room_sharing" value="exclusive"
           {{ $computerBookedActive ? 'disabled' : '' }}
           x-model="roomSharing">
    ...
</label>
```

For `shared`: no change — always enabled when `room_only` is the chosen type.

> **Alpine state guard:** if `$computerBookedActive` is true and the form's initial `roomSharing` is `exclusive` (e.g. from `old()` or a prefilled draft), the form should default `roomSharing` to `shared`. Either initialize it in Alpine's `data()` based on a Blade flag, or run a once-on-mount fixup:
>
> ```js
> // Inside Alpine x-init
> if ({{ $computerBookedActive ? 'true' : 'false' }} && this.roomSharing === 'exclusive') {
>     this.roomSharing = 'shared';
> }
> ```

#### 4e. Dynamic restriction when user changes time (no full reload)

The schedule page already calls `loadPcAvailability()` whenever date/time changes. Extend it to read the new API flag and re-apply restrictions:

```js
// Inside loadPcAvailability(), after this.pcAvailability is set:
this.hasComputerBookings = data.has_computer_bookings ?? false;
this.applyTypeRestrictions();
```

Add `hasComputerBookings: false` to Alpine `data()`.

Add a new method:

```js
applyTypeRestrictions() {
    // sharedRoomActive comes from the server draft (set when navigating from a shared-room slot)
    // and never changes during page lifetime, so we only manipulate computerBooked here.
    const computerBooked = this.hasComputerBookings;

    // full_room radio
    const fullRoom = this.$el.querySelector('input[name="type"][value="full_room"]');
    if (fullRoom) {
        const lbl = fullRoom.closest('label');
        if (computerBooked) {
            lbl?.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            fullRoom.disabled = true;
            if (this.selected === 'full_room') this.selected = '';
        } else if (!@json($sharedRoomActive)) {
            lbl?.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            fullRoom.disabled = false;
        }
    }

    // room_only exclusive sub-radio
    const exclusive = this.$el.querySelector('input[name="room_sharing"][value="exclusive"]');
    if (exclusive) {
        const lbl = exclusive.closest('label');
        if (computerBooked) {
            lbl?.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            exclusive.disabled = true;
            if (this.roomSharing === 'exclusive') this.roomSharing = 'shared';
        } else {
            lbl?.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            exclusive.disabled = false;
        }
    }
},
```

This makes the schedule page reactive: if the user changes the time to a slot with no computer bookings, `full_room` and `exclusive` re-enable automatically.

---

## What Does NOT Change

| Item | Reason |
|---|---|
| `BookingService::checkConflict()` | Already handles all combinations correctly; remains the last line of defense |
| `BookingService::typesConflict()` | Used by `autoRejectConflicting()`; correct as-is |
| `AvailabilityController::check()` | Existing booking-type conflict response is correct; we only extend `availableComputers()` |
| `AdminRequestController` | Admin approve/reject already does its own conflict check |
| `BookingStoreRequest` | Server-side validation unchanged — UI restriction is a UX layer, not security |
| `routes/web.php` / `routes/auth.php` | No new routes |
| Migrations / models | No schema changes |
| Calendar visual states (slot CSS classes, "Berbagi" / "Penuh" / "Saya" / "Menunggu") | All unchanged — `computerBooked` does not get a tile color |

---

## Edge Case Interactions

### EC-H + EC-A (shared-room exists in same slot)

Both flags true → `sharedRoom` is the stricter restriction (only `computers_only` allowed). The modal banner shows the sharedRoom message; both `Ruang + Komputer` and `Ruang Saja` cards are disabled. Same outcome whether or not the `computerBooked` flag is set. **Compatible — no special handling.**

### EC-H + EC-E (user's own booking — "Saya" slot)

User clicks their own `computers_only` "Saya" slot:
- `isMine` true, `computerBooked` true, `hardBlocked` false
- Modal opens (Saya slots are clickable)
- `Ruang + Komputer` disabled, `Ruang Saja → Eksklusif` disabled — correct, since adding either would conflict with the user's own existing `computers_only` booking
- Booking another `computers_only` for different PCs at the same slot — allowed and correct
- Booking `Ruang Saja → Berbagi` — allowed and correct

### EC-H + pending bookings

`$monthBookings` includes `submitted` / `under_review` / `approved`, so pending `computers_only` bookings also activate the restriction. This matches `BookingService::checkConflict(approvedOnly: false)` which would also reject a `full_room` submission against a pending `computers_only`. **Consistent.**

### EC-F (stale session draft via back button)

`?reset=1` on every "Buat Reservasi" entry point clears the entire `booking_draft` session, including the new `computer_booked_active` key. **No additional fix needed.**

### Schedule page: user changes time to an unrestricted slot

`loadPcAvailability()` runs on every date/time change → `has_computer_bookings: false` → `applyTypeRestrictions()` re-enables both `full_room` and `exclusive`. **Correctly dynamic.**

### Schedule page: user arrived via `?computer_booked=1` then changes time to a different slot that ALSO has computer bookings

`$computerBookedActive` (server-side) is true → static markup has `disabled` from the start. Then `loadPcAvailability()` returns `has_computer_bookings: true` → `applyTypeRestrictions()` keeps them disabled. Consistent state.

### Schedule page: arrived without the URL param but the chosen time has computer bookings

`$computerBookedActive` (server-side) is false → static markup is enabled. `loadPcAvailability()` returns `has_computer_bookings: true` → `applyTypeRestrictions()` disables them. The static amber banner does NOT appear (it's gated on `$computerBookedActive`), but the type cards are still correctly disabled. **Acceptable** — the user sees the restriction take effect even without a banner. A future enhancement could move the banner to be Alpine-rendered, but it's out of scope here.

---

## Verification Steps

1. **Setup:** create an approved `computers_only` booking for today 10:00–11:00 with PC-01 + PC-02.

2. **Calendar:** dashboard tile for that day at 10:00 still shows "Tersedia" / "Saya" (not "Penuh") — no new color. ✓

3. **Modal — type cards:**
   - Click 10:00 slot → modal opens
   - "Komputer" card enabled ✓
   - "Ruang + Komputer" card **disabled** ✓
   - "Ruang Saja" card **enabled** ✓
   - Amber banner visible ✓

4. **Modal — sharing sub-row:**
   - Click "Ruang Saja"
   - Sharing row appears
   - "Eksklusif" button **disabled** ✓
   - "Berbagi" button **enabled** and auto-selected ✓

5. **Modal navigation:** click "Buat Reservasi Sesi Ini" with "Ruang Saja → Berbagi" selected → schedule page loads with `?computer_booked=1` and `?room_sharing=shared` in URL → amber banner shows → `full_room` radio disabled → `room_only` card enabled with `exclusive` disabled, `shared` enabled. ✓

6. **Schedule page direct entry — dynamic restriction:**
   - Navigate to `/booking/schedule?reset=1`
   - Pick date today, time 10:00–11:00
   - After PC availability loads: `full_room` and `exclusive` disable; `room_only` card stays enabled; `shared` remains selectable
   - No static amber banner (acceptable — server-side flag was false)

7. **Schedule page — change to unrestricted time:**
   - On the same page, change time to 14:00–15:00 (no computer bookings)
   - After API call, `full_room` re-enables, `exclusive` re-enables. ✓

8. **Backend defense in depth:**
   - Via curl or browser dev tools, force-submit a `full_room` for the restricted slot
   - Backend returns `BookingConflictException` — user is redirected with error message. ✓ (no UI bypass)

9. **Combined restriction (EC-H + EC-A):**
   - Create a `room_only + shared` AND a `computers_only` for the same slot
   - Modal shows sharedRoom (teal) banner — both `Ruang + Komputer` and `Ruang Saja` cards disabled
   - Only `Komputer` selectable. ✓

10. **Stale flag clears via `?reset=1`:**
    - Click sidebar "Buat Reservasi" link → schedule page → no banner, all options enabled. ✓

---

## Risk Assessment

| Change | Risk | Mitigation |
|---|---|---|
| New `has_computer_bookings` API field | **Very Low** | Additive — existing consumers ignore unknown fields |
| New `$computerBookedBlocks` controller data | **Low** | Mirrors `$sharedRoomBlocks`; computation isolated; if it fails, dashboard degrades (no UI restriction) and backend still rejects on submit |
| Modal type-card + sub-button disabling | **Low** | Reuses the existing `.is-disabled` class and `selectResType()` / `selectSharing()` guards |
| Schedule page dynamic restriction via Alpine | **Medium** | New DOM manipulation method; must re-enable correctly when switching to a clean slot. Mitigated by verification step 7. |
| Combined sharedRoom + computerBooked banner logic | **Low** | sharedRoom case wins; both states yield correct disabled set |
| `computer_booked_active` draft key | **Very Low** | Same lifecycle as `shared_room_active`; cleared by existing `?reset=1` flow |

---

## File Change Summary

```
MODIFIED:
  app/Http/Controllers/Api/AvailabilityController.php   (add has_computer_bookings to availableComputers response)
  app/Http/Controllers/BookingController.php             (compute $computerBookedBlocks + computer_booked_active prefill)
  resources/views/dashboard.blade.php                    (JS: expose blocks, per-slot flag, modal disable, banner, navigateToBooking flag)
  resources/views/booking/schedule.blade.php             (Blade: $computerBookedActive read + banner + radio disable; Alpine: applyTypeRestrictions hook on loadPcAvailability)

NEW:    none
DELETED: none
```

No DB migration. No model change. No new routes. `BookingService`, admin flow, and auth are not touched.

---

## Implementation Order (When Executing)

1. Backend additive first (lowest risk, isolated):
   - `AvailabilityController` — add the flag
   - `BookingController::dashboard()` — add the computation + view var
   - `BookingController::showSchedule()` — add the prefill key
2. Dashboard JS (depends on step 1c output):
   - Expose `COMPUTER_BOOKED_BLOCKS`
   - Add `currentSlotIsComputerBooked` var
   - Compute `computerBooked` per slot
   - Update `openSlotModal()` (cards + sub-buttons + banner)
   - Update `navigateToBooking()` to carry `computer_booked=1`
3. Schedule view (depends on step 1c + step 2's URL param):
   - Read `$computerBookedActive`
   - Static banner
   - `full_room` radio disable
   - `exclusive` sub-radio disable + Alpine roomSharing fixup
4. Schedule Alpine dynamic layer:
   - `hasComputerBookings` in `data()`
   - `applyTypeRestrictions()` method
   - Call from `loadPcAvailability()` resolve

Manual smoke test after step 2 (modal works), again after step 3 (server-driven schedule restriction), again after step 4 (dynamic restriction). Each step is independently revertible if regressions appear.
