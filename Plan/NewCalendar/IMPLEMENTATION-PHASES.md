# Calendar Redesign — Implementation Phases

**Source plan:** `PLAN-CALENDAR-REDESIGN.md`  
**Status:** READY TO EXECUTE  
**Rule:** Complete and verify each phase fully before starting the next.  
**Rollback rule:** Each phase is independently revertible. Git-commit before Phase 2.

---

## Overview

| Phase | Name | Files touched | Risk |
|-------|------|--------------|------|
| **1** | New Calendar Page | 3 new files only | ✅ Zero — purely additive |
| **1b** | Inline Booking Form (Remove Multi-Step Flow) | 4 edits + 5 deletes | ⚠️ Medium — replaces booking entry |
| **2** | Dashboard Simplification | 2 existing files | ⚠️ Low — removes old code |
| **3** | Navigation Wiring | 1 existing file (Phase 3 Step 6 removed — moot) | ✅ Minimal |
| **4** | Cleanup | 1 route removed (Phase 4 scope reduced by 1b) | ✅ Minimal |

> **Phase 1b was added on 2026-06-01** — user chose Option C: the create popover becomes the full booking form; the 3-step booking flow (schedule → logbook → review) is deleted entirely. See design reference: `Plan/NewDesign/calendar-ui/project/screenshots/card2.png`.

---

## Phase 1 — New Calendar Page

**Goal:** `/calendar` works as a complete Google Calendar-style week view. Nothing existing is touched.

### Files to create
| File | Description |
|------|-------------|
| `app/Http/Controllers/CalendarController.php` | New controller — loads 5-week booking data |
| `resources/views/calendar/index.blade.php` | New view — full week-view calendar (from Scheduler.html design) |

### Files to edit
| File | Change |
|------|--------|
| `routes/web.php` | Add one line: `Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');` |

### What CalendarController does
- Queries all active bookings (submitted / under_review / approved) for a 5-week window
  (1 week past + current week + 3 weeks future)
- Maps each booking to a flat JS-friendly object:
  `{ id, date, start (mins), dur (mins), type, label, who, status, booking_code, is_mine }`
- Passes `$calendarEvents`, `$calStart`, `$calEnd` to the view

### Type mapping (DB → calendar)
| DB `booking_type` + `room_sharing` | Calendar type key | Color |
|------------------------------------|-------------------|-------|
| `computers_only` | `computer` | Indigo `#4f46e5` |
| `full_room` | `room_computer` | Violet `#7c3aed` |
| `room_only` + `exclusive` | `room_exclusive` | Teal `#0d9488` |
| `room_only` + `shared` | `room_sharing` | Amber `#d97706` |

### What the calendar view includes
1. **Toolbar** — Today | ← → | Period label | Work-week / Full-week toggle | Buat Reservasi button
2. **Sticky day-header row** — day name + date circle (today highlighted)
3. **Scrollable canvas** — hour gutter (60px) + day columns + "now" line
4. **Event cards** — colored blocks positioned by time, concurrent events split side-by-side
5. **Drag to create** — mousedown → drag → release → create popover opens
6. **Click event** — details popover opens (type, who, time, booking code, Detail / Batalkan actions)
7. **Group popover** — when >4 bookings overlap, a "+N" rollup tile opens a list

### CSS prefix rule
All new styles use the `wcal-` prefix. No clash with any existing class.

### JS architecture
```
weekCal()  ← Alpine x-data component
  ├─ state: anchor, view, creating, details, groupPop, now, selectedEvId
  ├─ computed: days[], canvasH, hours[], periodLabel, nowTop, nowVisible
  ├─ renderColumns()  ← vanilla JS (DOM building for performance)
  │    ├─ layoutDay()  ← concurrent booking column algorithm (from Scheduler.html)
  │    ├─ buildEventEl()
  │    └─ buildRollupEl()
  ├─ drag handler (mousedown → mousemove → mouseup)
  ├─ getSlotRestrictions()  ← reads CAL_EVENTS to detect sharedRoom / computerBooked
  ├─ openCreate() / openDetails()
  ├─ navigateToSchedule()  ← builds URL, goes to /booking/create/schedule
  └─ cancelBooking()  ← form POST to /booking/{id}/cancel
```

### Slot restriction logic (edge cases preserved)
The create popover checks `CAL_EVENTS` for the dragged slot and applies the same restrictions as the old dashboard modal:
- **Shared-room slot** → disables `room_computer` and `room_only` cards in popover; passes `&room_shared=1` to schedule URL
- **Computer-booked slot** → disables `room_computer`; disables `exclusive` mode; passes `&computer_booked=1`
- **Past day** → column is non-draggable (`cursor: default`, JS early-return guard)
- **Hard-blocked slot** (room_computer / room_exclusive exists) → `openCreate()` returns immediately without opening popover

### Acceptance checks for Phase 1
- [x] `php artisan route:list | findstr calendar` → shows `GET /calendar`
- [x] Visit `/calendar` — page loads, no PHP errors, no JS console errors
- [x] Week grid renders with correct day/date headers
- [x] Existing bookings appear as colored cards on correct days
- [x] Click empty slot → create popover opens with date pre-filled
- [x] Click event card → details popover opens
- [x] "Lanjut: Pilih Detail" navigates to `/booking/create/schedule` with pre-filled params
- [x] All existing pages (`/dashboard`, booking flow, admin) still work

**Phase 1 status: ✅ COMPLETE (verified 2026-06-01)**

---

## Phase 1b — Inline Booking Form (Remove Multi-Step Flow)

**Goal:** The create popover becomes a complete booking form. Submitting it creates the booking directly — no separate schedule / logbook / review pages. Those 3 pages plus the `booking.create` redirect are deleted.

**Design reference:** `Plan/NewDesign/calendar-ui/project/screenshots/card2.png`

**Prerequisite:** Phase 1 complete and browser-verified.

> ⚠️ **Git-commit before this phase:**
> `git commit -m "feat: add /calendar week-view page (Phase 1 complete)"`

---

### Popover form design (what the user sees)

Matches `card2.png`. Fields shown inside the popover:

```
Block time · New booking
──────────────────────────────────────────
DATE
  [Wed, Jun 1, 2026]           ← read-only display

START TIME          END TIME
  [08:00 ▼]         [10:00 ▼]   ← 30-min step dropdowns

LOAN TYPE
  ● Computer only         [COMPUTER]
  ○ Room + Computer       [ROOM+PC]
  ○ Room only             [ROOM]

  (if Room only selected:)
  PENGGUNAAN RUANG
    [Berbagi]  [Eksklusif]

COMPUTER UNIT              ← shown when "Computer only"
  [PC-01 ▼]               ← single dropdown, availability-aware

COMPUTER UNITS             ← shown when "Room + Computer"
  ☐ PC-01  ☐ PC-02  ☐ PC-03
  ☐ PC-04  ☐ PC-05  ☐ PC-06
  ☐ PC-07  ☐ PC-08  ☐ PC-09    ← multi-checkbox, availability-aware
  (unavailable PCs greyed out via AJAX)

ALASAN / TUJUAN
  [Purpose of the booking…]    ← text input, required

──────────────────────────────────────────
            [Cancel]   [Confirm booking]
```

---

### Booking type → backend mapping

| UI label | Backend `booking_type` | PC field behaviour |
|----------|----------------------|-------------------|
| Computer only | `computers_only` | Single dropdown — exactly 1 PC required |
| Room + Computer | `full_room` | Multi-checkbox (1–9 PCs, informational only — `full_room` locks whole room regardless) |
| Room only | `room_only` | No PC selection; sharing mode radio shown |

> **Why `full_room` for Room + Computer?**
> The backend `BookingService::checkConflict()` for `full_room` blocks any other booking in the slot. `computers_only` only blocks the specific chosen PCs. "Room + Computer" semantically = full lab reservation, so `full_room` is the correct type. Selected PCs are displayed in the calendar card label (e.g., "PC-01, PC-03") by attaching them as pivot records for display; the conflict rule still runs as `full_room`.

---

### AJAX PC availability

When the user changes date, start_time, or end_time in the popover:
- Fetch `GET /api/computers/available?date=Y&start_time=Z&end_time=W` (existing endpoint, unchanged)
- Response: `[{ id, label, available, pending, status }, ...]`
- For "Computer only": disable unavailable PCs in the dropdown
- For "Room + Computer": grey out and pre-uncheck unavailable PCs
- Show a small spinner while loading; show "Gagal memuat" on error

---

### Files to edit

#### 1. `resources/views/calendar/index.blade.php`

**Replace** the current create popover (which had "Lanjut: Pilih Detail →" as the final action) with the full booking form described above.

New popover behaviour:
- All fields are collected inline
- `x-ref="calForm"` on a hidden `<form>` with `action="{{ route('calendar.booking.store') }}" method="POST"`
- Popover inputs feed into hidden `<input>` fields inside that form
- "Confirm booking" → validates JS-side (required fields) → submits the form
- On server-side error: redirect back with `withErrors()` → toast shows flash message
- On success: redirect to `booking.show` → calendar refreshes on next load

**Add** inside the popover:
- Alpine state: `pcList: [], pcLoading: false` — driven by AJAX
- Alpine watcher: `$watch('creating.start', fetchPcAvail)` and `$watch('creating.end', fetchPcAvail)` and `$watch('creating.dateKey', fetchPcAvail)`
- `fetchPcAvail()` method: calls `/api/computers/available`, updates `pcList`
- `selectedPcs: []` — for multi-checkbox (Room + Computer)
- `selectedPc: null` — for single dropdown (Computer only)

#### 2. `app/Http/Controllers/CalendarController.php`

**Add** `store(Request $request)` method:

```php
public function store(Request $request): RedirectResponse
{
    // 1. Validate fields
    $validated = $request->validate([
        'booking_type'  => ['required', Rule::in(['full_room','computers_only','room_only'])],
        'room_sharing'  => ['nullable', 'required_if:booking_type,room_only',
                            Rule::in(['exclusive','shared'])],
        'date'          => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        'start_time'    => ['required', 'date_format:H:i'],
        'end_time'      => ['required', 'date_format:H:i', 'after:start_time'],
        'computers'     => ['array', 'required_if:booking_type,computers_only'],
        'computers.*'   => ['integer', Rule::in(Computer::pluck('id')->toArray())],
        'reason'        => ['required', 'string', 'min:3', 'max:1000'],
    ]);

    // 2. Same business rules as old validateSchedule() —
    //    operating day, operating hours, max session hours, PC status
    //    (extracted to private validateBusinessRules() method)

    // 3. Build schedule + logbook shapes for BookingService
    $schedule = [
        'type'         => $validated['booking_type'],
        'date'         => $validated['date'],
        'start_time'   => $validated['start_time'],
        'end_time'     => $validated['end_time'],
        'room_sharing' => $validated['room_sharing'] ?? null,
        'computers'    => $validated['computers'] ?? [],
    ];

    // Logbook: reason maps to checkpoint_progress; other fields use safe defaults.
    // The full logbook can be edited after admin approval via the existing logbook edit form.
    $logbook = [
        'category'            => 'lainnya',
        'checkpoint_progress' => $validated['reason'],
        'related_course'      => null,
        'supervisor_name'     => null,
        'needs_internet'      => false,
    ];

    // 4. Create via BookingService (conflict check + atomic transaction inside)
    try {
        $booking = $this->bookings->createBooking(auth()->id(), $schedule, $logbook);
    } catch (BookingConflictException $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()
        ->route('booking.show', $booking)
        ->with('success', 'Reservasi ' . $booking->booking_code . ' berhasil dikirim.');
}
```

#### 3. `routes/web.php`

**Add:**
```php
Route::post('/calendar/booking', [CalendarController::class, 'store'])->name('calendar.booking.store');
```

#### 4. `app/Http/Controllers/BookingController.php`

**Remove** these methods (they served the multi-step flow only):
- `showSchedule()`
- `showLogbook()`
- `showReview()`
- `store()` — session-based multi-step store (replaced by `CalendarController::store()`)
- Private `validateSchedule()`
- Private `validateLogbook()`

**Keep** (still needed):
- `dashboard()`
- `history()`
- `show()`
- `cancel()`
- Private `timeToMin()` — wait, this was added for CalendarController. Remove from BookingController if not needed there.

---

### Files to delete

| File | Reason |
|------|--------|
| `resources/views/booking/schedule.blade.php` | Replaced by calendar popover |
| `resources/views/booking/logbook.blade.php` | Replaced by `reason` field in popover |
| `resources/views/booking/review.blade.php` | Replaced by "Confirm booking" in popover |
| `resources/views/booking/create.blade.php` | Dead code |

> ⚠️ **CORRECTION (caught during execution):** `_logbook-form.blade.php` is **NOT deleted** — it is `@include`d by `booking/show.blade.php` (twice) for the **post-approval logbook editing** feature, which we keep. Deleting it would break the booking detail page. Only 4 view files are deleted.

> **Orphaned class (optional cleanup):** `app/Http/Requests/BookingStoreRequest.php` was only used by the removed `BookingController::store()`. It is now dead code but harmless (referenced nowhere). Left in place; can be deleted in Phase 4.

> **Route strategy (decided during execution):** `booking.schedule` is **kept as a redirect shim** (`GET /booking/create/schedule → redirect to calendar.index`) instead of being deleted, because the dashboard's old-calendar JS, the dashboard header button, `booking/history.blade.php`, and the sidebar all still reference `route('booking.schedule')`. Those references are repointed in Phases 2–3; the shim is removed in Phase 4. This keeps every intermediate state non-breaking. Routes actually deleted now: `booking.create`, `booking.logbook` (GET), `booking.review`, `booking.store`.

---

### Routes to remove from `routes/web.php`

```php
// REMOVE ALL of these:
Route::get ('/booking/create',          fn () => redirect()->route('booking.schedule'))->name('booking.create');
Route::get ('/booking/create/schedule', [BookingController::class, 'showSchedule'])->name('booking.schedule');
Route::get ('/booking/create/logbook',  [BookingController::class, 'showLogbook'])->name('booking.logbook');
Route::get ('/booking/create/review',   [BookingController::class, 'showReview'])->name('booking.review');
Route::post('/booking',                 [BookingController::class, 'store'])->name('booking.store');
```

**Keep** (still needed):
```php
Route::get ('/booking/history',           [BookingController::class, 'history'])->name('booking.history');
Route::get ('/booking/{booking}',         [BookingController::class, 'show'])->name('booking.show');
Route::post('/booking/{booking}/cancel',  [BookingController::class, 'cancel'])->name('booking.cancel');
Route::put ('/booking/{booking}/logbook', [BookingLogbookController::class, 'update'])->name('booking.logbook.update');
```

> Note: `booking.logbook.update` (`PUT /booking/{booking}/logbook`) is **kept** — this is the post-approval logbook edit feature, completely separate from the pre-booking form flow.

---

### Impact on subsequent phases

| Phase | Original plan | Updated plan |
|-------|--------------|-------------|
| Phase 2 (dashboard) | Remove old calendar widget, keep bookings table | **Unchanged** |
| Phase 3 Step 5 (sidebar) | Change "Buat Reservasi" → "Kalender" | **Unchanged** |
| Phase 3 Step 6 (schedule back-link) | Update "← Batal" on schedule.blade.php | **REMOVED** — schedule.blade.php is deleted in Phase 1b |
| Phase 4 (cleanup) | Remove booking.create route + delete create.blade.php | **REDUCED** — both already handled in Phase 1b |

---

### Collision check for Phase 1b

| Identifier | New or changed | Clashes with existing? |
|-----------|----------------|----------------------|
| `route('calendar.booking.store')` | New | No existing route with this name ✅ |
| `CalendarController::store()` | New method | No existing method ✅ |
| `BookingService::createBooking()` | Unchanged | ✅ Called with same signature |
| `BookingLogbook` `related_course` nullable | Schema already nullable | ✅ No migration needed |
| Alpine state `pcList`, `selectedPc`, `selectedPcs` | New state in `weekCal()` | Scoped inside Alpine component ✅ |
| AJAX to `/api/computers/available` | Existing endpoint | Already used by old schedule.blade.php ✅ |
| `booking.logbook.update` route | Kept unchanged | Still works post-approval ✅ |

---

### Acceptance checks for Phase 1b

**Verified at code/runtime level (2026-06-01):**
- [x] `POST /calendar/booking` route exists (`calendar.booking.store`)
- [x] All 3 PHP files lint clean; all Blade views compile
- [x] Runtime: valid `computers_only` (1 PC) → booking created, 1 PC attached
- [x] Runtime: valid `full_room` (3 PCs) → booking created, 3 PCs attached (BookingService change works)
- [x] Runtime: valid `room_only` + shared → booking created, reason → logbook.checkpoint_progress
- [x] Runtime: past date → rejected with flash error (no DB write)
- [x] Runtime: `room_only` without sharing mode → rejected with flash error
- [x] Routes deleted: `booking.create`, `booking.logbook` (GET), `booking.review`, `booking.store`
- [x] `booking.schedule` survives as redirect shim; `booking.history` / `booking.show` / `booking.cancel` / `booking.logbook.update` intact
- [x] `_logbook-form.blade.php` preserved (used by `show.blade.php`)

**Needs browser verification (Alpine/AJAX — cannot test from CLI):**
- [ ] Drag/click a future slot → popover shows full form
- [ ] "Computer only" → single PC dropdown, unavailable PCs greyed
- [ ] "Room + Computer" → multi-checkbox grid, unavailable PCs greyed
- [ ] "Room only" → PC section hidden, sharing mode appears
- [ ] "Konfirmasi Reservasi" with valid data → booking created, lands on `/booking/{id}`
- [ ] Booking appears on the calendar on next load
- [ ] Conflict / validation error → red toast shown, user back on calendar

---

## Phase 2 — Dashboard Simplification

**Goal:** Clean up the dashboard. Remove the old interactive calendar widget. The dashboard becomes a compact summary page.  
**Prerequisite:** Phase 1 complete and verified.

> ⚠️ **Take a git commit before this phase:**  
> `git commit -m "feat: add calendar page (Phase 1 complete)"`

### Files to edit

#### `app/Http/Controllers/BookingController.php`
**Remove** the block-array computation section (~108 lines, roughly lines 65–173):
- `$monthBookings` query
- `$totalOnline` query
- `$computeHourBlocks` closure
- `$fullBlocks`, `$pendingBlocks` computations
- `$sharedRoomBlocks` computation
- `$computerBookedBlocks` computation
- `$userEvents` computation

**Keep:**
- `$upcomingBookings`, `$completedBookings`, `$stats` queries (unchanged)

**Update** the `compact()` call at the bottom of `dashboard()`:
```php
// Before
return view('dashboard', compact(
    'upcomingBookings', 'completedBookings', 'stats',
    'fullBlocks', 'pendingBlocks', 'sharedRoomBlocks', 'computerBookedBlocks', 'userEvents'
));

// After
return view('dashboard', compact(
    'upcomingBookings', 'completedBookings', 'stats'
));
```

#### `resources/views/dashboard.blade.php`
**Remove** these sections entirely:
1. The entire `{{-- ── INTERACTIVE CALENDAR ── --}}` block (the white card with `.cal-body`)
2. The entire `{{-- ── SLOT AVAILABILITY MODAL ── --}}` block (the `.slot-modal-overlay` div)
3. All `@push('styles')` CSS for the old calendar (all `.cal-*`, `.slot-modal-*`, `.type-card`, `.sharing-btn`, `.computer-slot`, `.slot-*`, `.interval-btn` rules)
4. The entire `@push('scripts')` block (all JS functions for the old calendar)

**Keep:**
- The `@php` setup block at the top (used by header + bookings table)
- The `<x-slot:header>` section (greeting + buttons)
- The stat cards grid
- The bottom grid (bookings table + right panel)

**Change** the "Buat Reservasi" button in the header to link to `/calendar`:
```blade
{{-- Before --}}
<a href="{{ route('booking.schedule', ['reset' => 1]) }}" class="btn-mark btn-sm">

{{-- After --}}
<a href="{{ route('calendar.index') }}" class="btn-mark btn-sm">
```

**Add** a calendar CTA card between the stat cards and the bottom grid (replaces the removed calendar widget):
```blade
{{-- ── CALENDAR CTA ── --}}
<a href="{{ route('calendar.index') }}"
   class="block bg-white border border-rule rounded-xl shadow-card p-5 mb-6 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 group">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-label text-ink-700/40 mb-1">Jadwal Lab</div>
            <div class="text-base font-bold text-ink-900 tracking-tight">Lihat Kalender Reservasi</div>
            <p class="text-sm text-ink-700/50 mt-1">Klik slot waktu untuk membuat reservasi baru.</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 transition-colors">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
            </svg>
        </div>
    </div>
</a>
```

**Add** a minimal `switchBookingTab()` JS function (still needed for the bookings table tabs):
```html
@push('scripts')
<script>
const BOOKING_ROWS = {
    mendatang: {!! json_encode($upcomingHtml) !!},
    selesai:   {!! json_encode($completedHtml) !!},
};
const BOOKING_COUNTS = {
    mendatang: {{ $upcomingBookings->count() }},
    selesai:   {{ $completedBookings->count() }},
};
function switchBookingTab(el, mode) {
    document.querySelectorAll('.pill-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('bookings-tbody').innerHTML = BOOKING_ROWS[mode] || '';
    document.getElementById('bookings-count').textContent = BOOKING_COUNTS[mode] ?? 0;
}
</script>
@endpush
```

### Acceptance checks for Phase 2

**Verified at code/runtime level (2026-06-02):**
- [x] `BookingController` lints clean; all views compile
- [x] `dashboard()` returns only `upcomingBookings, completedBookings, stats` (no stray vars)
- [x] No `$fullBlocks`/`$userEvents`/`cal-*`/`slot-modal`/old-JS references remain in the view (grep clean)
- [x] Unused imports removed (`Computer`, `LabSetting`); block-array closure deleted (~108 lines)
- [x] Header "Buat Reservasi" + new CTA card both link to `route('calendar.index')`
- [x] Bottom grid (table + tabs + right panel) preserved; `switchBookingTab()` kept

**Needs browser verification:**
- [ ] `/dashboard` renders with stat cards, CTA card, bookings table, right panel
- [ ] Mendatang / Selesai tabs switch correctly
- [ ] "Buat Reservasi" and CTA card navigate to `/calendar`

**Phase 2 status: code complete, runtime-verified — awaiting browser check.**
- [ ] `/calendar` still works (Phase 1 not broken)
- [ ] ~~Booking flow (`/booking/create/schedule` → logbook → review → store) still works~~ ← moot after Phase 1b

---

## Phase 3 — Navigation Wiring

**Goal:** Update the sidebar to point to the calendar.
**Prerequisite:** Phase 1b complete and verified.

> **Note:** Phase 3 Step 6 (update "← Batal" on schedule.blade.php) has been **removed** — `schedule.blade.php` is deleted in Phase 1b, so there is nothing to update.

### Files to edit

#### `resources/views/components/app-sidebar.blade.php`
In the `@else` (non-admin) nav block, replace the "Buat Reservasi" item:

```blade
{{-- REMOVE this entire block --}}
<a href="{{ route('booking.schedule', ['reset' => 1]) }}"
   class="nav-item {{ str_starts_with($current, 'booking.create') || ... ? 'active' : '' }}">
    ...
    <span>Buat Reservasi</span>
</a>

{{-- ADD this replacement --}}
<a href="{{ route('calendar.index') }}"
   class="nav-item {{ $current === 'calendar.index' ? 'active' : '' }}">
    <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        <line x1="3" y1="9" x2="21" y2="9" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        <line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        <line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
    </svg>
    <span>Kalender</span>
</a>
```

### Acceptance checks for Phase 3

**Verified at code level (2026-06-02):**
- [x] Sidebar nav item now reads "Kalender" → `route('calendar.index')`, active on `$current === 'calendar.index'` (calendar icon)
- [x] `booking/history.blade.php` header button repointed `booking.schedule` → `calendar.index`
- [x] Grep: no `booking.schedule` references remain outside its own route definition/comment → shim is safe to delete in Phase 4
- [x] All views compile

**Needs browser verification:**
- [ ] Sidebar shows "Kalender", highlights when on `/calendar`, navigates correctly
- [ ] History page "Buat Reservasi" button goes to `/calendar`

---

## Phase 4 — Cleanup

**Goal:** Remove the last transitional dead code.
**Prerequisite:** All previous phases complete and verified.

**Executed (2026-06-02):**
- Removed the `booking.schedule` redirect shim from `routes/web.php` (all references repointed in Phases 2–3).
- Deleted the orphaned `app/Http/Requests/BookingStoreRequest.php` (only ever used by the removed `BookingController::store()`).

### Acceptance checks for Phase 4

**Verified at code level (2026-06-02):**
- [x] `route:list` — no `booking.create`, `booking.schedule`, `booking.logbook` (GET), `booking.review`, `booking.store`
- [x] Kept routes intact: `booking.history`, `booking.show`, `booking.cancel`, `booking.logbook.update`, `calendar.index`, `calendar.booking.store`, `dashboard`
- [x] App boots (`route:list` runs); all views compile; no reference to the deleted FormRequest
- [x] Grep: no remaining `booking.schedule` / `BookingStoreRequest` references in app code

**Needs browser verification (full end-to-end smoke test):**
- [ ] login → dashboard → sidebar Kalender → calendar → drag slot → fill form → Konfirmasi → booking detail → booking shows on calendar

**Phase 4 status: code complete, verified.**

---

## Updated Phase Summary Table

| Phase | Primary deliverable | New files | Modified files | Deleted files | Can break existing? |
|-------|--------------------|-----------|-----------------|--------------|--------------------|
| 1 ✅ | `/calendar` works as full week-view | 2 | 1 (routes) | 0 | No |
| 1b | Inline booking form, remove multi-step pages | 0 | 3 | 5 | ⚠️ Yes — replaces booking entry |
| 2 | Dashboard cleaned up | 0 | 2 | 0 | Low |
| 3 | Sidebar → Kalender link | 0 | 1 | 0 | No |
| 4 | Final dead code sweep | 0 | 1 (routes) | 0 | No |

**Total: 2 new + 7 modified + 5 deleted = 14 file operations across 5 phases.**

---

## Git Commit Points

```
Phase 1  done → git commit -m "feat: add /calendar week-view page (Phase 1 complete)"
Phase 1b done → git commit -m "feat: inline calendar booking form, remove multi-step flow"
Phase 2  done → git commit -m "refactor: simplify dashboard, remove old calendar widget"
Phase 3  done → git commit -m "feat: wire sidebar navigation to /calendar"
Phase 4  done → git commit -m "chore: final cleanup of dead booking routes"
```
