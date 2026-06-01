# PLAN — Calendar Redesign (Dashboard Simplification + New Calendar Page)

**Date:** 2026-06-01  
**Status:** PLANNED — not yet executed  
**Author:** Developer (Claude)  
**Requested by:** Project Manager  

---

## 1. Goal

Replace the current interactive calendar on the dashboard with a dedicated **Google Calendar-style week view page** (`/calendar`), implemented from the `Plan/NewDesign/calendar-ui/` design prototype. Simultaneously simplify the dashboard to a clean summary page and remove the now-dead `/booking/create` type-selection step.

---

## 2. Scope

### What changes
| Item | Change |
|------|--------|
| `dashboard.blade.php` | Remove the entire interactive calendar section (the two-panel widget with month grid + slot panel + slot modal overlay). Keep stat cards, bookings table, right panel. |
| `resources/views/components/app-sidebar.blade.php` | Replace "Buat Reservasi" nav item (→ `/booking/schedule`) with "Kalender" (→ `/calendar`). Update active-state detection. |
| `routes/web.php` | Add `GET /calendar` route. Remove the `/booking/create` redirect. |
| `app/Http/Controllers/BookingController.php` | Simplify `dashboard()` — remove block-array computation. Keep stats + bookings queries. |
| `resources/views/booking/schedule.blade.php` | No logic changes. Only: update "← Batal" link to go to `/calendar` instead of `/dashboard`. |

### What is added (new)
| Item | Purpose |
|------|---------|
| `app/Http/Controllers/CalendarController.php` | Serves `/calendar`. Loads 5-week calendar events. |
| `resources/views/calendar/index.blade.php` | Full-page week-view calendar translated from `Scheduler.html`. |

### What is deleted
| Item | Reason |
|------|--------|
| `resources/views/booking/create.blade.php` | Dead code — the `booking.create` route has redirected to `booking.schedule` since the auth rework. No view has ever linked to it directly. |

### What does NOT change (backend stays 100% intact)
- `BookingService.php` — conflict detection, race-safe logic, auto-reject
- `AvailabilityController.php` — AJAX endpoints still called by `schedule.blade.php` and the new calendar
- `booking/schedule.blade.php` — form logic, Alpine component, PC grid, availability check
- `booking/logbook.blade.php`, `booking/review.blade.php`
- `BookingController::store()`, `showLogbook()`, `showReview()`, `cancel()`, `history()`, `show()`
- All admin controllers and views
- All migrations, models, seeders
- Auth flow (login, admin-login, middleware)

---

## 3. New URL Structure

| Route name | URL | Controller | Purpose |
|------------|-----|------------|---------|
| `calendar.index` | `GET /calendar` | `CalendarController::index()` | New calendar page |
| `booking.schedule` | `GET /booking/create/schedule` | (unchanged) | Booking form entry |
| `dashboard` | `GET /dashboard` | `BookingController::dashboard()` | Summary page |

The `booking.create` route (previously `GET /booking/create → redirect`) is **removed entirely**.

---

## 4. Data Flow

```
/calendar (CalendarController::index)
│
├─ Loads: all active bookings for 5-week window
│   → calendarEvents[] (id, date, start, dur, type, label, who, status, booking_code, is_mine)
│   → calStart, calEnd (Carbon dates for JS range clamping)
│
└─ Views: calendar/index.blade.php
    │
    ├─ CAL_EVENTS = @json($calendarEvents)  [PHP → JS]
    ├─ weekCal() Alpine function            [JS state machine]
    │   ├─ layoutDay()                      [concurrent booking algorithm]
    │   ├─ buildDayColumn()                 [vanilla JS DOM]
    │   ├─ buildEventEl()                   [event card DOM]
    │   └─ buildRollupEl()                  [+N overflow card DOM]
    │
    ├─ Create popover (Alpine x-show)
    │   └─ "Lanjut" → GET /booking/create/schedule?type=X&date=Y&start_time=Z&end_time=W[&room_sharing=V]
    │
    └─ Details popover (Alpine x-show)
        └─ "Detail" → GET /booking/{id}
        └─ "Batalkan" → POST /booking/{id}/cancel  (form submit)
```

```
/dashboard (BookingController::dashboard) — simplified
│
├─ Loads: upcomingBookings, completedBookings, stats
└─ No longer loads: fullBlocks, pendingBlocks, sharedRoomBlocks, computerBookedBlocks, userEvents
```

---

## 5. Step-by-Step Implementation Sequence

Execute in this exact order to avoid broken intermediary states.

### Step 1 — Add route + CalendarController (no view yet)
**File:** `routes/web.php`
- Add: `Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');`
- Remove: `Route::get('/booking/create', fn () => redirect()->route('booking.schedule'))->name('booking.create');`

**File:** `app/Http/Controllers/CalendarController.php` (CREATE)
- Method `index()`: query bookings for 5-week window, map to `$calendarEvents`, pass to view
- Add private `timeToMin(string $time): int` helper

**Verify:** `php artisan route:list | grep calendar` shows the new route. No 500 errors.

---

### Step 2 — Create the calendar view (new file)
**File:** `resources/views/calendar/index.blade.php` (CREATE)

Structure:
```
<x-app-layout>
  @push('styles') ... week calendar CSS (all prefixed wcal-) ... @endpush

  <x-slot:header>
    <!-- Page title "Kalender Lab" + greeting line -->
  </x-slot:header>

  <!-- Calendar card -->
  <div class="... overflow-hidden" x-data="weekCal()" x-init="init()">

    <!-- Toolbar: Today | ← → | Period label | Work/Full toggle | + Buat Reservasi btn -->

    <!-- .wcal-scroll (overflow-y:auto, max-height: calc(100vh - Npx)) -->
      <!-- .wcal-head (sticky, grid: 60px repeat(N, 1fr)) -->
      <!-- .wcal-canvas (grid: 60px repeat(N, 1fr), height: canvasH px) -->
        <!-- .wcal-gutter + hour labels -->
        <!-- #wcal-day-cols (display:contents — filled by JS) -->
        <!-- .wcal-now-line (absolute, left:60px) -->

    <!-- Create popover (x-show="creating") -->
    <!-- Details popover (x-show="details") -->
    <!-- Group popover (x-show="groupPop") -->

  </div>

  @push('scripts') ... weekCal() function + helpers ... @endpush
</x-app-layout>
```

**Verify:** `/calendar` loads without JS errors. Week grid renders. Events appear (if any exist in the 5-week window).

---

### Step 3 — Simplify BookingController::dashboard()
**File:** `app/Http/Controllers/BookingController.php`

- **Remove** (lines ~65–173): `$monthBookings`, `$totalOnline`, `$computeHourBlocks` closure, `$fullBlocks`, `$pendingBlocks`, `$sharedRoomBlocks`, `$computerBookedBlocks`, `$userEvents`
- **Keep**: `$upcomingBookings`, `$completedBookings`, `$stats`
- **Update** `compact()` call: remove the six deleted variables
- Also remove unused `use App\Models\Computer;` import if it's no longer needed (check — `Computer` may be used elsewhere in the same controller — if not, remove)

**Verify:** `/dashboard` still loads without errors. Stat cards show correct numbers. Bookings table renders.

---

### Step 4 — Rewrite dashboard.blade.php calendar section
**File:** `resources/views/dashboard.blade.php`

- **Remove** the entire `{{-- ── INTERACTIVE CALENDAR ── --}}` section (the card with `.cal-body`, `.cal-grid-panel`, `.cal-slots-panel`)
- **Remove** the `{{-- ── SLOT AVAILABILITY MODAL ── --}}` section (the full `.slot-modal-overlay` div)
- **Remove** all `@push('styles')` CSS that is exclusively for the old calendar (`.cal-*`, `.slot-modal-*`, `.type-card`, `.sharing-btn`, `.computer-slot`, `.slot-*`, `.interval-btn`, `.pill-tabs`, `.bookings-tbl` etc.)
- **Remove** the entire `@push('scripts')` block (all JS: `renderCalendar`, `calNav`, `selectDay`, `buildTimeSlots`, `setSlotInterval`, `renderTimeSlots`, `resetSlots`, `selectResType`, `selectSharing`, `openSlotModal`, `renderModalComputers`, `togglePcSelection`, `navigateToBooking`, `closeSlotModal`, `switchBookingTab`, `renderCalendar()` invocation)
- **Keep**: stat cards HTML, the bottom grid (bookings table + right panel), the `@php` setup block, the header slot
- **Add**: a simple `switchBookingTab()` JS function for the bookings table (the table still exists and has tabs)
- **Add**: a small "Go to Calendar" call-to-action card in place of the removed calendar, pointing to `/calendar`

**Verify:** `/dashboard` renders correctly with stat cards, the CTA card, and the bookings table. No undefined JS errors.

---

### Step 5 — Update sidebar
**File:** `resources/views/components/app-sidebar.blade.php`

In the `@else` (non-admin) nav section:

**Remove:**
```blade
<a href="{{ route('booking.schedule', ['reset' => 1]) }}"
   class="nav-item {{ str_starts_with($current, 'booking.create') || ... ? 'active' : '' }}">
    <svg ...>...</svg>
    <span>Buat Reservasi</span>
</a>
```

**Add:**
```blade
<a href="{{ route('calendar.index') }}"
   class="nav-item {{ $current === 'calendar.index' ? 'active' : '' }}">
    <svg class="nav-item-icon" ...><!-- calendar grid icon --></svg>
    <span>Kalender</span>
</a>
```

**Verify:** Sidebar shows "Kalender" link. Clicking it navigates to `/calendar`. Active state highlights correctly on `/calendar`.

---

### Step 6 — Update schedule.blade.php back-link
**File:** `resources/views/booking/schedule.blade.php`

Line ~378:
```blade
<!-- Before -->
<a href="{{ route('dashboard') }}" class="btn-ghost">← Batal</a>

<!-- After -->
<a href="{{ route('calendar.index') }}" class="btn-ghost">← Batal</a>
```

This makes the cancel action return to the calendar (where the user came from) rather than the dashboard.

**Verify:** On `/booking/create/schedule`, clicking "← Batal" returns to `/calendar`.

---

### Step 7 — Delete dead files
- Delete `resources/views/booking/create.blade.php`

**Verify:** File no longer exists. No routes reference it. No `route('booking.create')` calls exist anywhere.

---

## 6. Collision Prevention Checklist

This section documents every identifier that could clash with existing code.

### CSS Classes
All new calendar CSS uses the `wcal-` prefix. No existing class in `app.css` or any existing Blade view uses this prefix.

| New class | Conflicts with? | Safe? |
|-----------|----------------|-------|
| `.wcal-scroll` | Nothing | ✅ |
| `.wcal-head` | Nothing | ✅ |
| `.wcal-canvas` | Nothing | ✅ |
| `.wcal-gutter` | Nothing | ✅ |
| `.wcal-day-col` | `.cal-slots-panel` (old) — REMOVED in Step 4 | ✅ |
| `.wcal-ev` | Nothing | ✅ |
| `.wcal-now-line` | Nothing | ✅ |
| `.wcal-pop` | `.slot-modal` (old) — REMOVED in Step 4 | ✅ |
| `.wcal-pop-overlay` | `.slot-modal-overlay` (old) — REMOVED in Step 4 | ✅ |

**Action:** Remove all old `.cal-*`, `.slot-modal-*` CSS in Step 4 before writing new `.wcal-*` CSS.

### JS Global Variables
| New constant | Location | Conflicts? |
|-------------|----------|-----------|
| `CAL_EVENTS` | `calendar/index.blade.php` | Only in that view's `<script>` tag. Not in dashboard. ✅ |
| `CAL_LOAD_START` | Same | Same ✅ |
| `CAL_LOAD_END` | Same | Same ✅ |
| `BOOKING_TYPES` | Same | No existing global with this name ✅ |
| `DAY_SHORT_ID` | Same | Old dashboard had `dayNames` (local var in function). ✅ |
| `MONTHS_ID` | Same | Old dashboard had `MONTHS_ID` as a const in the page script — but old dashboard script is **removed in Step 4**, so no clash ✅ |
| `LOAN_OPTS` | Same | New name, no conflict ✅ |

### JS Functions
| New function | Location | Conflicts? |
|-------------|----------|-----------|
| `weekCal()` | `calendar/index.blade.php` | New Alpine function. Not in any other view. ✅ |
| `layoutDay()` | Same | New. ✅ |
| `fmtMin()` | Same | Old dashboard had local `fmt` inside functions. `fmtMin` is distinct. ✅ |
| `startOfDay()` | Same | Old dashboard had `TODAY` constant. `startOfDay` is a new named function. ✅ |
| `addDays()`, `sameDay()`, `dateKey()` | Same | New, same-scope helpers. ✅ |
| `showBlockOverlay()`, `updateBlockOverlay()` | Same | New, only in this view. ✅ |
| `snap()` | Same | New local helper. ✅ |

**Key:** The old dashboard's `renderCalendar`, `calNav`, `openSlotModal`, `navigateToBooking`, etc. are **removed in Step 4**. They do NOT exist in the new dashboard and do NOT conflict with the new calendar page (which is a separate view file).

### Alpine.js Components
| Component name | View | Conflicts? |
|---------------|------|-----------|
| `weekCal()` | `calendar/index.blade.php` | New component. No other view uses this name. ✅ |
| `bookingForm()` | `booking/schedule.blade.php` | Unchanged. ✅ |
| Body `x-data` | `app.blade.php` (`sidebarOpen, mobileOpen`) | Unchanged. Lives on `<body>`, parent of all. ✅ |

Alpine.js scopes are per-element. `weekCal()` on the calendar div and the `<body>` data do not interfere.

### PHP Controller Methods
| Method | Controller | Change? |
|--------|------------|--------|
| `dashboard()` | `BookingController` | Simplified — only removes block-array code |
| `showSchedule()` | `BookingController` | No change |
| `index()` | `CalendarController` | New method |
| `timeToMin()` | Both controllers | **Risk:** Both `CalendarController` and `BookingController` need this helper. Solution: define it as `private function timeToMin()` in each controller independently. Do NOT create a shared trait — unnecessary abstraction for one two-line function. |

### Route Names
| New name | Conflicts? |
|---------|-----------|
| `calendar.index` | No existing route with this name. ✅ |

### View Namespace
| New view path | Conflicts? |
|--------------|-----------|
| `calendar.index` | No existing `resources/views/calendar/` directory. ✅ |

---

## 7. CalendarController Data Specification

```php
// app/Http/Controllers/CalendarController.php

public function index(): View
{
    // 5-week window: 1 past + current + 3 future
    $calStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks(1);
    $calEnd   = $calStart->copy()->addWeeks(5)->endOfWeek(Carbon::SUNDAY);

    $calendarEvents = Booking::with(['user:id,name', 'computers:id,label,unit_number'])
        ->whereIn('status', ['submitted', 'under_review', 'approved'])
        ->whereBetween('date', [$calStart->toDateString(), $calEnd->toDateString()])
        ->get()
        ->map(fn ($b) => $this->toCalEvent($b))
        ->values()
        ->toArray();

    return view('calendar.index', compact('calendarEvents', 'calStart', 'calEnd'));
}

private function toCalEvent(Booking $b): array
{
    // type mapping:
    // computers_only → 'computer'
    // full_room      → 'room_computer'
    // room_only + exclusive → 'room_exclusive'
    // room_only + shared    → 'room_sharing'
    ...
}

private function timeToMin(string $time): int
{
    $parts = explode(':', substr($time, 0, 5));
    return (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
}
```

**Event shape passed to JS:**
```json
{
  "id": 42,
  "date": "2026-06-02",
  "start": 540,
  "dur": 120,
  "type": "computer",
  "label": "PC-03",
  "who": "Dr. Budi Santoso",
  "status": "approved",
  "booking_code": "LAB-0023",
  "is_mine": true
}
```

`start` and `dur` are in **minutes from midnight** (e.g., 09:00 = 540, 2h = 120).

---

## 8. Calendar Page JS Architecture

The `weekCal()` Alpine function manages state. Vanilla JS handles DOM-intensive calendar rendering (event cards, concurrent layout). Communication between them uses a module-level `self` reference.

```
weekCal() Alpine function
├── State: anchor (Date), view, creating, details, groupPop, block, now, selectedEvId
├── Computed: days[], canvasH, hours[], periodLabel, nowTop, nowVisible, nowLabel
├── Methods:
│   ├── init()            → assigns self, starts clock tick, triggers first render
│   ├── go(dir)           → advance anchor ±1 week, re-render
│   ├── goToday()         → reset to today, re-render
│   ├── setView(v)        → toggle work/week, re-render
│   ├── renderColumns()   → vanilla JS: builds day-col divs + event cards
│   ├── buildDayColumn()  → creates col div, attaches drag handler, adds event/rollup cards
│   ├── buildEventEl()    → creates .wcal-ev div, applies layoutDay() geometry
│   ├── buildRollupEl()   → creates .wcal-rollup div for +N overflow
│   ├── openCreate()      → computes restrictions, sets creating state
│   ├── openDetails()     → sets details state, triggers re-render (for selected ring)
│   ├── navigateToSchedule() → builds URL, window.location.href to /booking/schedule
│   ├── cancelBooking()   → confirm + form POST to /booking/{id}/cancel
│   └── popPos(rect, h)   → positions popover near anchor element, clamps to viewport
│
├── Drag logic (mousedown on day column)
│   └── tracks dragState, shows .wcal-block-sel overlay, on mouseup calls openCreate()
│
└── Slot restriction logic (getSlotRestrictions)
    ├── Reads CAL_EVENTS for the dragged slot
    ├── Returns { hardBlocked, sharedRoom, computerBooked }
    └── Create popover uses this to disable type options (mirrors old modal's EC-C/D/H logic)
```

---

## 9. Create Popover → Booking Flow

When user clicks/drags on the calendar and clicks "Lanjut: Pilih Jadwal →":

```
URL built:
/booking/create/schedule?type={computers_only|full_room|room_only}
                        &date={YYYY-MM-DD}
                        &start_time={HH:MM}
                        &end_time={HH:MM}
                        [&room_sharing={exclusive|shared}]   (if type=room_only)
                        [&room_shared=1]                     (if slot has shared-room booking)
                        [&computer_booked=1]                 (if slot has computer booking)
```

`BookingController::showSchedule()` already handles all these params (unchanged). It pre-fills the session draft and redirects to the clean form URL. The booking flow continues exactly as before.

**Edge cases preserved:**
- EC-C: `room_shared=1` still passed → schedule page disables full_room and room_only
- EC-H: `computer_booked=1` still passed → schedule page disables full_room and exclusive
- EC-I: Past day columns are non-draggable/non-clickable (CSS `cursor:default` + JS guard)
- Shared-room slot restriction: `getSlotRestrictions()` checks live CAL_EVENTS before opening popover

---

## 10. Dashboard After Simplification

Post-Step-4, the dashboard contains only:

```
Header: "Halo, {name}" + next booking label + [Lihat Riwayat] [Buat Reservasi → /calendar]
Stat cards (4): Sesi Mendatang | Total Bulan Ini | Menunggu | Total Pemakaian
CTA card: "Lihat Kalender Lab →" (replaces the removed calendar widget)
Bottom grid:
  Left: Bookings table (upcoming / selesai tabs)
  Right: Jam Operasional + Status Lab
```

The "Buat Reservasi" button in the dashboard header changes to link to `/calendar` (the user picks a slot there). The bookings table tab-switching JS (`switchBookingTab`) is a standalone function — kept and unaffected.

---

## 11. BookingController::dashboard() After Simplification

Removes ~108 lines of block-array computation. Keeps:
- `$upcomingBookings` (with computers loaded) — for table
- `$completedBookings` — for table
- `$stats` (5 keys) — for stat cards

No longer passes: `$fullBlocks`, `$pendingBlocks`, `$sharedRoomBlocks`, `$computerBookedBlocks`, `$userEvents`.

No longer imports/uses: `Computer::where('status', 'online')->count()` for dashboard purposes (Computer model still imported for `showSchedule()`).

---

## 12. Acceptance Criteria

| # | Check | How to verify |
|---|-------|--------------|
| 1 | `/calendar` loads without JS errors | Open browser console — no errors |
| 2 | Week grid shows current week's bookings as colored cards | Log in, create a test booking, check calendar |
| 3 | Clicking empty slot opens create popover | Click any future slot |
| 4 | Popover "Lanjut" navigates to schedule page pre-filled | Confirm date/time/type fields are pre-filled |
| 5 | Shared-room slot shows teal banner, disables room types in popover | Create room_only+shared booking, then check that slot |
| 6 | Computer-booked slot shows amber banner, disables full_room | Create computers_only booking, check that slot |
| 7 | Past day columns are non-draggable | Try clicking a past date column |
| 8 | Event card click opens details popover | Click a booking card |
| 9 | Details "Detail" button navigates to `/booking/{id}` | Click Detail on own booking |
| 10 | Concurrent bookings split side-by-side (no overlap) | Create two overlapping bookings |
| 11 | `/dashboard` loads without the old calendar | No `.cal-body` div present |
| 12 | Dashboard stat cards show correct numbers | Match numbers to DB |
| 13 | Dashboard bookings table works (tab switching) | Click Mendatang / Selesai tabs |
| 14 | Sidebar shows "Kalender" and highlights when on `/calendar` | Check sidebar on both pages |
| 15 | `booking.create` route is gone | `php artisan route:list` shows no `booking.create` |
| 16 | `create.blade.php` is deleted | File not present |
| 17 | "← Batal" on schedule page goes to `/calendar` | Click cancel on the booking form |
| 18 | Full booking flow still works end-to-end | Calendar → drag → popover → schedule → logbook → review → submit → see booking on calendar |

---

## 13. Files Summary Table

| File | Action | Reason |
|------|--------|--------|
| `app/Http/Controllers/CalendarController.php` | **CREATE** | Serves calendar data |
| `resources/views/calendar/index.blade.php` | **CREATE** | New calendar page |
| `app/Http/Controllers/BookingController.php` | **EDIT** `dashboard()` | Remove block-array code |
| `resources/views/dashboard.blade.php` | **EDIT** | Remove calendar section + old JS/CSS |
| `resources/views/components/app-sidebar.blade.php` | **EDIT** | Rename nav item |
| `resources/views/booking/schedule.blade.php` | **EDIT** | Update back-link |
| `routes/web.php` | **EDIT** | Add calendar route, remove booking.create |
| `resources/views/booking/create.blade.php` | **DELETE** | Dead code |

**Total files: 6 edits + 2 creates + 1 delete = 9 file operations.**

---

## 14. Rollback Plan

If anything breaks mid-implementation, the steps are independent enough to revert:
- Steps 1–2 (new route + new view) can be reverted by deleting the new files and removing the route line
- Steps 3–4 (dashboard simplification) are the only irreversible edits — **take a git commit before Step 3**
- Step 5 (sidebar) — single line change, trivially revertible
- Step 6 (schedule back-link) — single line change, trivially revertible
- Step 7 (delete create.blade.php) — can be recreated from git history

**Recommended:** `git commit -m "chore: pre-calendar-redesign snapshot"` before starting Step 3.
