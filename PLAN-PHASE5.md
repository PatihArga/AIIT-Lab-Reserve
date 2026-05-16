# PLAN-PHASE5 — Core Booking Backend
> **Status:** Ready to implement
> **Phase:** 5 of 9
> **Stack:** Laravel 12 · MySQL · Blade · Alpine.js · Tailwind CSS v3
> **App URL:** `http://localhost/UKRIDA_LabReserve/public`
> **Last updated:** 2026-05-16

---

## TABLE OF CONTENTS

1. [Before You Start](#1-before-you-start)
2. [Scope — What Phase 5 Covers](#2-scope--what-phase-5-covers)
3. [What Phase 5 Does NOT Touch](#3-what-phase-5-does-not-touch)
4. [Discovered Issues to Fix First](#4-discovered-issues-to-fix-first)
5. [Architecture Decisions](#5-architecture-decisions)
6. [New Schema Change](#6-new-schema-change)
7. [Data Contracts](#7-data-contracts)
8. [Conflict Detection Rules](#8-conflict-detection-rules)
9. [File Inventory](#9-file-inventory)
10. [Implementation Steps](#10-implementation-steps)
11. [Route Map (Before → After)](#11-route-map-before--after)
12. [Success Criteria](#12-success-criteria)

---

## 1. BEFORE YOU START

Run these checks before writing a single line of code. Do not proceed if any fail.

```bash
# 1. Confirm the database is migrated and seeded
php artisan migrate:status
# Every migration should show "Ran"

# 2. Confirm seeded data exists
php artisan tinker --execute="echo App\Models\Computer::count();"
# Expected: 9

php artisan tinker --execute="echo App\Models\LabSetting::get('buffer_minutes');"
# Expected: 15

# 3. Confirm the app loads without error
# Visit: http://localhost/UKRIDA_LabReserve/public/admin/login
# Should render login page without exception
```

**Tables that must exist before Phase 5:**
| Table | Created by |
|-------|-----------|
| `study_programs` | `0001_01_01_000000_create_users_table.php` |
| `users` | `0001_01_01_000000_create_users_table.php` |
| `teams` | `2024_01_01_000003_create_teams_table.php` |
| `team_members` | `2024_01_01_000003_create_teams_table.php` |
| `computers` | `2024_01_01_000004_create_computers_table.php` |
| `bookings` | `2024_01_01_000005_create_bookings_table.php` |
| `booking_computers` | `2024_01_01_000005_create_bookings_table.php` |
| `booking_logbooks` | `2024_01_01_000006_create_booking_logbooks_table.php` |
| `audit_logs` | `2024_01_01_000007_create_audit_logs_table.php` |
| `lab_settings` | `2024_01_01_000008_create_lab_settings_table.php` |

---

## 2. SCOPE — WHAT PHASE 5 COVERS

Phase 5 wires the booking creation flow end-to-end and makes all user-facing
booking pages serve real database data.

**In scope:**

| # | Feature | Entry point |
|---|---------|-------------|
| 5.1 | Schema fix + model bug fix | Migration + `Booking.php` |
| 5.2 | `BookingService` (conflict detection + creation) | New file |
| 5.3 | `BookingController` (all user booking actions) | New file |
| 5.4 | `LogbookController` (save/update logbook) | New file |
| 5.5 | `BookingStoreRequest` (validation) | New file |
| 5.6 | Route wiring — all booking routes | `routes/web.php` |
| 5.7 | Booking creation views — session data + real submit | 3 views modified |
| 5.8 | Schedule page — real computers from DB | `booking/schedule.blade.php` |
| 5.9 | History page — real booking list | `booking/history.blade.php` |
| 5.10 | Detail page — real booking data | `booking/show.blade.php` |
| 5.11 | Logbook edit — real save action | `booking/_logbook-form.blade.php` |
| 5.12 | Dashboard — real stat cards + booking table | `dashboard.blade.php` |
| 5.13 | AJAX availability endpoint — slot check | New API route |
| 5.14 | AJAX available computers endpoint | New API route |

---

## 3. WHAT PHASE 5 DOES NOT TOUCH

Do not modify these during Phase 5. They belong to later phases.

| What | Reason |
|------|--------|
| Admin approval/reject logic | Phase 6 |
| Email notifications | Phase 7 |
| Google Calendar sync | Phase 7 |
| `AuditLog` writes | Phase 8 (stub only in Phase 5) |
| Reports and analytics | Phase 8 |
| Lab settings form backend | Phase 8 |
| All admin views | Phase 6 |
| Password reset backend | Phase 6 |
| `create.blade.php` | Not in current flow; leave untouched |
| Auth views and login logic | Phase 2 is complete; do not touch |
| All component `.blade.php` files | Already built; no changes needed |
| `app-sidebar.blade.php` | Already complete |
| `layouts/app.blade.php` | Already complete |

---

## 4. DISCOVERED ISSUES TO FIX FIRST

These are bugs/mismatches found during code review. Fix them in Step 5.1
before any new logic is added.

### Issue A — `Booking::isEditable()` checks wrong statuses

**File:** `app/Models/Booking.php:44`

```php
// CURRENT (wrong):
public function isEditable(): bool
{
    return in_array($this->status, ['approved', 'under_review']);
}

// CORRECT (matches what show.blade.php uses):
public function isEditable(): bool
{
    return in_array($this->status, ['approved', 'completed']);
}
```

**Why it matters:** `show.blade.php` uses `in_array($booking->status, ['approved', 'completed'])` directly.
`Booking::isEditable()` must agree with this, otherwise the controller will
give a different answer than the view.

---

### Issue B — Form field `reason` does not match DB column `checkpoint_progress`

**File:** `resources/views/booking/logbook.blade.php:40`

The booking creation form uses `name="reason"` for the textarea.
The `booking_logbooks` table column is `checkpoint_progress`.
The logbook edit form (`booking/_logbook-form.blade.php:11`) correctly uses
`name="checkpoint_progress"`.

**Fix:** Rename the field in `logbook.blade.php` from `reason` to
`checkpoint_progress`. Update the label to match the DB semantics.
This is a 2-character change in one view.

---

### Issue C — `review.blade.php` submit button has no form wrapping it

**File:** `resources/views/booking/review.blade.php:112`

The "Kirim Permintaan" button exists but is not inside any `<form>` tag.
It cannot POST anything in the current state.

**Fix:** Wrap the page's submit section in a `<form method="POST">` that
reads accumulated data from session as hidden inputs. This is done in Step 5.7.

---

## 5. ARCHITECTURE DECISIONS

### 5.1 Multi-Step Form — Session Strategy

**Problem:** The 3-step booking form passes data via GET query strings across
steps. Step 2 (logbook) does not carry Step 1's data forward in its own form,
so by the time the user reaches Step 3 (review), Step 1's data is no longer
available in the URL.

**Decision: Server-side session accumulation.**

When a controller handles each step transition, it validates the incoming data
and writes it into Laravel session under the key `booking_draft`:

```
booking_draft.schedule  → set when user submits Step 1 → Step 2
booking_draft.logbook   → set when user submits Step 2 → Step 3
```

At Step 3 (review), the controller reads from session to display the full
summary. The submit button sends `POST /booking`, and the store controller
reads all data from session.

**Why session, not hidden inputs?**
- Hidden inputs expose all booking data in the HTML (visible in page source).
- A session keeps data server-side and allows us to validate at each boundary.
- If a user navigates backwards, their session data re-populates the correct step.

**Session lifecycle:**
- Created: when Step 1 data is validated (entering logbook page).
- Updated: when Step 2 data is validated (entering review page).
- Destroyed: on `POST /booking` success (redirect to show page).
- Also destroyed: if user navigates to `/dashboard` (cancel link).

**If session data is missing at Step 3:** redirect back to `/booking/create/schedule`.

---

### 5.2 Conflict Detection Strategy

Conflict detection runs in `BookingService::checkConflict()` inside a
database transaction with `lockForUpdate()` to prevent race conditions.

Booking types have different conflict rules (see Section 8 for full spec).

Buffer time (default 15 min) is read from `LabSetting::get('buffer_minutes', 15)`.
The buffer applies to both ends of a booking: a booking from 09:00–12:00 with
a 15-min buffer effectively occupies 08:45–12:15 for conflict purposes.

**Race condition safety:**
All conflict checks and booking creation happen inside a single
`DB::transaction(fn() => ...)`. The `lockForUpdate()` call on the bookings
query ensures no two simultaneous requests can both pass the conflict check.

---

### 5.3 Booking Code Generation

Format: `LAB-NNNN` where NNNN is a zero-padded 4-digit sequential integer.
Example: `LAB-0001`, `LAB-0042`, `LAB-9999`.

Generation happens inside the same transaction as booking creation:

```php
$last = Booking::lockForUpdate()->max('booking_code');
$next = $last ? ((int) substr($last, 4)) + 1 : 1;
$code = 'LAB-' . str_pad($next, 4, '0', STR_PAD_LEFT);
```

This is safe: `lockForUpdate()` prevents two transactions from reading the
same `max` value simultaneously.

---

### 5.4 Dashboard Calendar — Deferred to Phase 5.13

The dashboard uses a fully custom JS calendar (not FullCalendar). Feeding it
real booking data requires a JSON endpoint. This is in scope for Phase 5 but
is implemented last (Step 5.13), after all other steps are complete and tested.

The dashboard stat cards and booking table below the calendar are wired in
Step 5.12 using standard Blade data.

---

## 6. NEW SCHEMA CHANGE

The `booking/schedule.blade.php` and `booking/create.blade.php` views include
a `room_sharing` field (`exclusive` or `shared`) for `room_only` bookings.
This field does not exist in the `bookings` table. It affects conflict
detection (a shared room_only booking does not block a computers_only booking).

**Action:** Add a migration to add `room_sharing` to `bookings`.

```
File: database/migrations/2024_01_02_000001_add_room_sharing_to_bookings.php
```

```php
Schema::table('bookings', function (Blueprint $table) {
    $table->enum('room_sharing', ['exclusive', 'shared'])
          ->nullable()
          ->default(null)
          ->after('booking_type');
    // NULL means not applicable (full_room or computers_only)
    // 'exclusive' or 'shared' only applies when booking_type = 'room_only'
});
```

**Constraint:** `room_sharing` must be `null` when `booking_type` is not `room_only`.
This is enforced at the validation layer, not at DB level (no DB-level check constraint
needed for simplicity).

**After adding the migration:** run `php artisan migrate` before implementing any
booking logic.

---

## 7. DATA CONTRACTS

### 7.1 Session Keys

All booking draft data lives under the `booking_draft` session key.

```php
// Written by BookingController::showLogbook()
session(['booking_draft.schedule' => [
    'type'         => 'computers_only',   // full_room | computers_only | room_only
    'room_sharing' => null,               // null | exclusive | shared (only for room_only)
    'date'         => '2026-05-12',       // YYYY-MM-DD
    'start_time'   => '09:00',           // HH:MM
    'end_time'     => '12:00',           // HH:MM
    'computers'    => [1, 3, 5],         // array of Computer IDs (empty for room_only/full_room)
]]);

// Written by BookingController::showReview()
session(['booking_draft.logbook' => [
    'category'          => 'penelitian',
    'checkpoint_progress' => 'Pengumpulan data…',
    'related_course'    => 'Kecerdasan Buatan',
    'supervisor_name'   => 'Dr. Budi',
    'needs_internet'    => true,
]]);
```

### 7.2 Field Name Mappings

These mismatches between form field names and DB column names must be handled
in the controller/request, OR the view must be updated to use the DB name.

| View file | Form field name | DB column | Resolution |
|-----------|----------------|-----------|------------|
| `booking/logbook.blade.php` | `reason` | `checkpoint_progress` | **Rename form field** to `checkpoint_progress` in the view (Issue B fix) |
| `booking/_logbook-form.blade.php` | `checkpoint_progress` | `checkpoint_progress` | Already correct |
| `booking/logbook.blade.php` | `needs_internet` (checkbox, value="1") | `needs_internet` (boolean) | Cast `"1"` → `true` in controller |

### 7.3 Data Displayed on Review Page

The review page must read from `session('booking_draft')` and display:

| Display label | Source |
|---------------|--------|
| Booking type (human-readable) | `booking_draft.schedule.type` translated |
| Date (formatted) | `booking_draft.schedule.date` formatted as `d M Y` in Indonesian |
| Day of week | Derived from date |
| Start time | `booking_draft.schedule.start_time` |
| End time | `booking_draft.schedule.end_time` |
| Duration | Computed from start/end |
| Selected computers | Computer labels from `booking_draft.schedule.computers` (DB lookup) |
| Category | `booking_draft.logbook.category` translated |
| Checkpoint/Progress | `booking_draft.logbook.checkpoint_progress` |
| Related course | `booking_draft.logbook.related_course` |
| Internet needed | `booking_draft.logbook.needs_internet` |

### 7.4 Validation Rules

#### Step 1 — Schedule (validated in `BookingController::showLogbook`)

| Field | Rule |
|-------|------|
| `type` | `required`, `in:full_room,computers_only,room_only` |
| `room_sharing` | `required_if:type,room_only`, `in:exclusive,shared`, `nullable` |
| `date` | `required`, `date`, `date_format:Y-m-d`, `after_or_equal:today` |
| `start_time` | `required`, `date_format:H:i` |
| `end_time` | `required`, `date_format:H:i`, `after:start_time` |
| `computers` | `required_if:type,computers_only`, `array`, `min:1` |
| `computers.*` | `integer`, `exists:computers,id` |

**Additional business rules (validated in controller, not FormRequest):**
- `date` must be an operating day (Mon–Sat, from `LabSetting::get('operating_days')`)
- `start_time` must be ≥ `LabSetting::get('operating_start')` (08:00)
- `end_time` must be ≤ `LabSetting::get('operating_end')` (22:00)
- Duration must be ≤ `LabSetting::get('max_session_hours')` (4 hours)
- All `computers` IDs must have `status = 'online'` (maintenance excluded)

#### Step 2 — Logbook (validated in `BookingController::showReview`)

| Field | Rule |
|-------|------|
| `checkpoint_progress` | `required`, `string`, `min:10`, `max:2000` |
| `category` | `required`, `in:penelitian,project_akademik,praktikum,tugas_akhir,lainnya` |
| `related_course` | `required`, `string`, `max:255` |
| `supervisor_name` | `nullable`, `string`, `max:255` |
| `needs_internet` | `nullable`, `boolean` |

> `related_course` is `required` in the current form (`logbook.blade.php`).

#### Final Submit (validated in `BookingStoreRequest`)

The final POST `/booking` only validates that the session data is intact:
- `session('booking_draft.schedule')` exists and is not empty
- `session('booking_draft.logbook')` exists and is not empty

All field-level validation already happened at Steps 1 and 2. Do not re-validate
every field at the store step — trust what was already validated and stored in session.

#### Logbook Update (validated inline in `LogbookController::update`)

| Field | Rule |
|-------|------|
| `checkpoint_progress` | `required`, `string`, `min:10`, `max:2000` |
| `session_target` | `nullable`, `string`, `max:2000` |
| `supervisor_name` | `nullable`, `string`, `max:255` |
| `related_course` | `nullable`, `string`, `max:255` |

---

## 8. CONFLICT DETECTION RULES

This is the most critical logic in Phase 5. Get this right.

### 8.1 Time Overlap with Buffer

Two bookings A and B on the same date conflict if their buffered time windows overlap.

```
Buffered window = [start_time - buffer_minutes, end_time + buffer_minutes]

Overlap exists if NOT (A.buffered_end <= B.buffered_start OR B.buffered_end <= A.buffered_start)
```

> Buffer is read from `LabSetting::get('buffer_minutes', 15)`.
> Buffer is applied symmetrically: before the start AND after the end.
> Only bookings with status in `['submitted', 'under_review', 'approved']`
> count as active for conflict purposes. `draft`, `rejected`, `cancelled`,
> `completed` do NOT block a slot.

### 8.2 Conflict Rules by Booking Type

#### Full Room (`full_room`)
- Blocks the ENTIRE lab for the time window.
- Conflicts with: **any** existing active booking on the same date that overlaps (regardless of type).
- A new `full_room` booking cannot be created if ANYTHING exists in that slot.

#### Computers Only (`computers_only`)
- Reserves specific computer units. Does not claim the room exclusively.
- Conflicts with:
  1. Any existing `full_room` booking that overlaps.
  2. Any existing `computers_only` booking that overlaps AND shares at least 1 computer unit.
- Does NOT conflict with:
  - Overlapping `room_only` bookings (they don't use computers).
  - Overlapping `computers_only` bookings that use different units.

#### Room Only — Exclusive (`room_only` + `room_sharing = exclusive`)
- Claims the physical room exclusively, without computers.
- Conflicts with:
  1. Any existing `full_room` booking that overlaps.
  2. Any existing `room_only` booking (exclusive OR shared) that overlaps.
- Does NOT conflict with: overlapping `computers_only` bookings.

#### Room Only — Shared (`room_only` + `room_sharing = shared`)
- Uses the room but allows others to also use it.
- Conflicts with:
  1. Any existing `full_room` booking that overlaps.
  2. Any existing `room_only` exclusive booking that overlaps.
- Does NOT conflict with:
  - Overlapping `computers_only` bookings.
  - Overlapping `room_only` shared bookings.

### 8.3 Conflict Check SQL Logic

`BookingService::checkConflict()` must be called inside `DB::transaction()`.

```php
// Pseudocode — implement this in BookingService
public function checkConflict(
    string $date,
    string $startTime,
    string $endTime,
    string $bookingType,
    array  $computerIds = [],      // only for computers_only
    string $roomSharing = null,    // only for room_only
    int    $excludeBookingId = null // for future edit scenarios
): bool {
    $buffer    = (int) LabSetting::get('buffer_minutes', 15);
    $buffStart = Carbon::parse($startTime)->subMinutes($buffer)->format('H:i');
    $buffEnd   = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i');

    $active = ['submitted', 'under_review', 'approved'];

    // Base query: all active bookings on same date that overlap in time
    $overlapping = Booking::where('date', $date)
        ->whereIn('status', $active)
        ->where('start_time', '<', $buffEnd)
        ->where('end_time', '>', $buffStart)
        ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
        ->lockForUpdate();

    if ($bookingType === 'full_room') {
        return $overlapping->exists();
    }

    if ($bookingType === 'computers_only') {
        // Conflict if any full_room exists, OR any computers_only shares a unit
        $hasFullRoom = (clone $overlapping)->where('booking_type', 'full_room')->exists();
        if ($hasFullRoom) return true;

        if (empty($computerIds)) return false;

        $hasSharedComputer = (clone $overlapping)
            ->where('booking_type', 'computers_only')
            ->whereHas('computers', fn($q) => $q->whereIn('computers.id', $computerIds))
            ->exists();

        return $hasSharedComputer;
    }

    if ($bookingType === 'room_only') {
        // Always conflicts with full_room
        $hasFullRoom = (clone $overlapping)->where('booking_type', 'full_room')->exists();
        if ($hasFullRoom) return true;

        if ($roomSharing === 'exclusive') {
            // Exclusive: conflicts with any other room_only
            return (clone $overlapping)->where('booking_type', 'room_only')->exists();
        }

        if ($roomSharing === 'shared') {
            // Shared: only conflicts with room_only exclusive
            return (clone $overlapping)
                ->where('booking_type', 'room_only')
                ->where('room_sharing', 'exclusive')
                ->exists();
        }
    }

    return false;
}
```

---

## 9. FILE INVENTORY

### New Files to Create

| File | Purpose |
|------|---------|
| `app/Services/BookingService.php` | Core logic: conflict check + booking creation |
| `app/Http/Controllers/BookingController.php` | User-facing booking actions |
| `app/Http/Controllers/BookingLogbookController.php` | Save/update logbook |
| `app/Http/Requests/BookingStoreRequest.php` | Final submit validation |
| `database/migrations/2024_01_02_000001_add_room_sharing_to_bookings.php` | New column |

### Existing Files to Modify

| File | What changes |
|------|-------------|
| `app/Models/Booking.php` | Fix `isEditable()` (Issue A) |
| `routes/web.php` | Wire all booking routes to controllers; add POST routes; add API routes |
| `resources/views/booking/schedule.blade.php` | Feed real computers from DB; keep form unchanged |
| `resources/views/booking/logbook.blade.php` | Rename `reason` → `checkpoint_progress` (Issue B); add step-1 session error flash |
| `resources/views/booking/review.blade.php` | Replace dummy data with session data; add POST form with hidden inputs |
| `resources/views/booking/history.blade.php` | Replace `$bookings` PHP array with real paginated Eloquent collection |
| `resources/views/booking/show.blade.php` | Replace dummy object with real `$booking` Eloquent model; fix action URLs |
| `resources/views/booking/_logbook-form.blade.php` | Add real form action URL; pre-fill existing values on edit |
| `resources/views/dashboard.blade.php` | Replace hardcoded `RESERVATIONS` const and stat numbers with real data |

### Files That Stay Exactly As-Is

All component files, layouts, auth views, admin views, migrations (except new one),
models (except `Booking.php`), seeders, and CSS/JS assets.

---

## 10. IMPLEMENTATION STEPS

Execute steps in order. Do not start the next step until the current step's
verify check passes.

---

### STEP 5.0 — Verify Database State

**Goal:** Confirm all required tables exist and contain seeded data.

```bash
php artisan migrate:status
# All migrations must show "Ran"

php artisan tinker
>>> App\Models\Computer::count()     // 9
>>> App\Models\LabSetting::count()   // 8
>>> App\Models\User::where('role','admin')->count() // 1
```

**Verify:** All counts return expected values.
If any migration is missing, run `php artisan migrate`.
If seeders haven't run, run `php artisan db:seed`.

---

### STEP 5.1 — Schema Addition + Model Fix

**Goal:** Add `room_sharing` column and fix `Booking::isEditable()`.

**Actions:**
1. Create migration `2024_01_02_000001_add_room_sharing_to_bookings.php`
   - Add nullable enum column `room_sharing` with values `['exclusive', 'shared']`
   - Place it after the `booking_type` column
2. Run `php artisan migrate`
3. In `app/Models/Booking.php`:
   - Add `room_sharing` to `$fillable`
   - Fix `isEditable()` to check `['approved', 'completed']`
4. Add `BookingComputer` model (or confirm it's not needed — the pivot is accessed via `belongsToMany`, so no separate model is required)

**Verify:**
```bash
php artisan migrate:status
# New migration shows "Ran"

php artisan tinker
>>> Schema::hasColumn('bookings', 'room_sharing')  // true
>>> (new App\Models\Booking(['status' => 'approved']))->isEditable()  // true
>>> (new App\Models\Booking(['status' => 'under_review']))->isEditable()  // false
>>> (new App\Models\Booking(['status' => 'completed']))->isEditable()  // true
```

---

### STEP 5.2 — Create `BookingService`

**File:** `app/Services/BookingService.php`

**Goal:** Implement conflict detection and booking creation in a single, testable service.

**Methods to implement:**

```php
class BookingService
{
    public function checkConflict(
        string $date,
        string $startTime,
        string $endTime,
        string $bookingType,
        array  $computerIds = [],
        ?string $roomSharing = null,
        ?int   $excludeBookingId = null
    ): bool

    public function createBooking(
        int    $userId,
        array  $scheduleData,  // from session booking_draft.schedule
        array  $logbookData    // from session booking_draft.logbook
    ): Booking
}
```

**`createBooking` must:**
1. Open a `DB::transaction()`
2. Call `checkConflict()` inside the transaction (with `lockForUpdate`)
3. If conflict: throw a custom `BookingConflictException` or return `null`
4. Generate booking code (see Section 5.3 of architecture decisions)
5. Create `Booking` record with status `submitted` and `submitted_at = now()`
6. If `booking_type = computers_only`: attach computer IDs via `$booking->computers()->attach($computerIds)`
7. Create `BookingLogbook` record linked to the booking
8. Return the created `Booking` instance

**Error:** If conflict is detected, `createBooking` throws
`App\Exceptions\BookingConflictException` with a user-readable message.

**`BookingConflictException`** (create this simple class):
```php
// app/Exceptions/BookingConflictException.php
class BookingConflictException extends \Exception {}
```

**Verify (manual tinker test):**
```bash
php artisan tinker
>>> $service = new App\Services\BookingService();
>>> $service->checkConflict('2099-01-01', '09:00', '12:00', 'full_room')
# false (no bookings exist)
```

---

### STEP 5.3 — Create `BookingController`

**File:** `app/Http/Controllers/BookingController.php`

**Methods:**

| Method | Route | Purpose |
|--------|-------|---------|
| `showSchedule()` | `GET /booking/create/schedule` | Load computers from DB; return view |
| `showLogbook(Request)` | `GET /booking/create/logbook` | Validate Step 1; store in session; return view |
| `showReview(Request)` | `GET /booking/create/review` | Validate Step 2; store in session; return view with data |
| `store(BookingStoreRequest)` | `POST /booking` | Create booking from session data; clear session |
| `history(Request)` | `GET /booking/history` | Return paginated booking list for auth user |
| `show(Booking)` | `GET /booking/{booking}` | Return booking detail (ownership check) |
| `cancel(Request, Booking)` | `POST /booking/{booking}/cancel` | Cancel booking (ownership + status check) |

**Detail notes:**

**`showSchedule()`**
- Load all computers (`Computer::orderBy('unit_number')->get()`)
- Pass to view as `$computers`
- Return `view('booking.schedule', compact('computers'))`

**`showLogbook(Request $request)`**
- Validate Step 1 fields (see Section 7.4 validation rules)
- If validation fails: redirect back with errors
- Validate business rules (operating day, hours, duration, computers online status)
- If business rules fail: redirect back with errors (use `withErrors` + `withInput`)
- Write to `session(['booking_draft.schedule' => [...validated data...]])`
- Return `view('booking.logbook')`

**`showReview(Request $request)`**
- Guard: if `session('booking_draft.schedule')` is null, redirect to `booking.schedule`
- Validate Step 2 fields (see Section 7.4 validation rules)
- If validation fails: redirect back with errors
- Write to `session(['booking_draft.logbook' => [...validated data...]])`
- Retrieve computer labels from DB for display: if computers selected, load them
- Return `view('booking.review', compact('draft', 'computerLabels'))`
  where `$draft` = full `session('booking_draft')`

**`store(BookingStoreRequest $request)`**
- Guard: if either session key is missing, redirect to `booking.schedule`
- Call `BookingService::createBooking()` inside a try/catch
- On `BookingConflictException`: redirect to `booking.schedule` with error
- On success: clear `session('booking_draft')`; redirect to `booking.show` with success flash
- Do not write email or calendar here (Phase 7)

**`history(Request $request)`**
- Query: `auth()->user()->bookings()->with(['computers'])->latest('date')->paginate(15)`
- Filter by status if `?status=` query param is present
- Filter by date if `?date=` query param is present
- Return `view('booking.history', compact('bookings'))`

**`show(Booking $booking)`**
- Use route model binding: `Route::get('/booking/{booking}', ...)`
- Ownership check: `abort_if($booking->user_id !== auth()->id(), 403)`
- Eager-load: `$booking->load(['computers', 'logbook'])`
- Return `view('booking.show', compact('booking'))`

**`cancel(Request $request, Booking $booking)`**
- Ownership check: `abort_if($booking->user_id !== auth()->id(), 403)`
- Status check: `abort_if(!$booking->isCancellable(), 422, 'Reservasi tidak dapat dibatalkan.')`
- Update: `$booking->update(['status' => 'cancelled'])`
- Do not trigger email or calendar here (Phase 7)
- Redirect to `booking.show` with success flash

---

### STEP 5.4 — Create `BookingLogbookController`

**File:** `app/Http/Controllers/BookingLogbookController.php`

**Single method:**

```php
public function update(Request $request, Booking $booking): RedirectResponse
```

- Ownership check: `abort_if($booking->user_id !== auth()->id(), 403)`
- Editability check: `abort_if(!$booking->isEditable(), 403)`
- Validate fields (see Section 7.4 logbook validation rules)
- `updateOrCreate`: if logbook exists, update it; if not, create it
  ```php
  $booking->logbook()->updateOrCreate(
      ['booking_id' => $booking->id],
      $validated
  );
  ```
- Redirect to `booking.show` with success flash

---

### STEP 5.5 — Create `BookingStoreRequest`

**File:** `app/Http/Requests/BookingStoreRequest.php`

This FormRequest only validates that the session data is present.
It does NOT re-validate field-by-field (that was done at Steps 1 and 2).

```php
public function authorize(): bool
{
    return auth()->check();
}

public function rules(): array
{
    return []; // No field rules — session presence is checked in the controller
}
```

The actual session presence guard is in `BookingController::store()`.

---

### STEP 5.6 — Wire Routes

**File:** `routes/web.php`

Replace all closure-returning-view routes with controller method calls.
Add POST routes. Add API routes.

**Full replacement for booking section:**

```php
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingLogbookController;

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', [BookingController::class, 'dashboard'])->name('dashboard');

    // Booking creation flow (3 steps)
    Route::get('/booking/create', fn() => redirect()->route('booking.schedule'))->name('booking.create');
    Route::get('/booking/create/schedule', [BookingController::class, 'showSchedule'])->name('booking.schedule');
    Route::get('/booking/create/logbook',  [BookingController::class, 'showLogbook'])->name('booking.logbook');
    Route::get('/booking/create/review',   [BookingController::class, 'showReview'])->name('booking.review');
    Route::post('/booking',                [BookingController::class, 'store'])->name('booking.store');

    // Booking management
    Route::get('/booking/history',             [BookingController::class, 'history'])->name('booking.history');
    Route::get('/booking/{booking}',           [BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{booking}/cancel',   [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::put('/booking/{booking}/logbook',   [BookingLogbookController::class, 'update'])->name('booking.logbook.update');

    // Profile (unchanged)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes (unchanged closures for now — Phase 6 will wire these)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // ... keep all existing admin closures unchanged ...
    });
});
```

**Add API routes in `routes/api.php`:**

```php
use App\Http\Controllers\Api\AvailabilityController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/check-availability',   [AvailabilityController::class, 'check']);
    Route::get('/computers/available',  [AvailabilityController::class, 'availableComputers']);
});
```

> Note: For XAMPP development, Sanctum may need `stateful` domains configured.
> If Sanctum causes trouble, temporarily use web middleware for API routes and add CSRF handling.

**Add `dashboard` method to BookingController** (since the dashboard route
now points to controller):
```php
public function dashboard(): View
{
    // See Step 5.12 for full implementation
}
```

---

### STEP 5.7 — Update Views for Session Data

**Goal:** Connect the 3 booking creation views to real session data.

#### A. `booking/schedule.blade.php`

The form structure stays exactly as-is. Two changes only:

1. Replace the hardcoded `$dummyComputers` PHP block with the real `$computers`
   variable passed from the controller:
   ```php
   // REMOVE this @php block (lines 218–223):
   @php
       $dummyComputers = collect(range(1, 9))->map(fn($n) => (object)[...]);
   @endphp

   // The component call becomes:
   <x-computer-grid :computers="$computers" :selectable="true" name="computers" />
   ```

2. Add session error flash display at the top of the form (inside the `<div class="max-w-2xl mx-auto">`):
   ```blade
   @if ($errors->any())
   <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
       <ul class="list-disc list-inside space-y-1">
           @foreach ($errors->all() as $error)
               <li>{{ $error }}</li>
           @endforeach
       </ul>
   </div>
   @endif
   ```

#### B. `booking/logbook.blade.php`

1. **Fix Issue B:** Rename form field from `reason` to `checkpoint_progress`:
   - Line ~40: `<label>Alasan Peminjaman` → `<label>Checkpoint / Progress Kegiatan`
   - Line ~41: `name="reason"` → `name="checkpoint_progress"`

2. Add error flash display (same pattern as above, after the `<p class="text-sm…">` intro).

3. Add `@if(session('error'))` flash display for business rule failures.

No hidden fields needed — Step 1 data was already stored in session by the controller.

#### C. `booking/review.blade.php`

This is the most significant view change in Phase 5.

**Remove:** All the hardcoded dummy data rows.

**Add:** Read from `$draft` variable (passed by controller from session):

```php
@php
    $schedule = $draft['schedule'];
    $logbook  = $draft['logbook'];

    $typeLabelMap = [
        'full_room'      => 'Ruang + Komputer',
        'computers_only' => 'Komputer Saja',
        'room_only'      => 'Ruang Saja',
    ];
    $categoryMap = [
        'penelitian'      => 'Penelitian',
        'project_akademik'=> 'Project Akademik',
        'praktikum'       => 'Praktikum',
        'tugas_akhir'     => 'Tugas Akhir / Skripsi',
        'lainnya'         => 'Lainnya',
    ];
    $dateObj  = \Carbon\Carbon::parse($schedule['date']);
    $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $dayName  = $dayNames[$dateObj->dayOfWeek];
    $dateFormatted = $dateObj->translatedFormat('d M Y');
    $start    = $schedule['start_time'];
    $end      = $schedule['end_time'];
    $durationHours = \Carbon\Carbon::parse($start)->diffInMinutes(\Carbon\Carbon::parse($end)) / 60;
    $durationLabel = ($durationHours == intval($durationHours))
        ? intval($durationHours) . ' jam'
        : $durationHours . ' jam';
@endphp
```

**Replace submit button with a proper POST form:**

```blade
<form method="POST" action="{{ route('booking.store') }}">
    @csrf
    <div class="flex items-center justify-between pt-6 border-t border-rule">
        <a href="{{ route('booking.logbook') }}" class="btn-ghost">
            ← Kembali
        </a>
        <button type="submit" class="btn-mark btn-lg">
            <svg ...>...</svg>
            Kirim Permintaan
        </button>
    </div>
</form>
```

No hidden inputs needed — all data is in session server-side.

---

### STEP 5.8 — Wire History Page

**File:** `resources/views/booking/history.blade.php`

**Goal:** Replace the hardcoded `$bookings` PHP array with a real paginated
Eloquent collection.

1. Remove the `@php $bookings = [...] @endphp` block (lines 19–27).

2. Update the view to use Eloquent model attributes instead of array keys:
   - `$b['code']` → `$b->booking_code`
   - `$b['type']` → human-readable label from `$b->booking_type`
   - `$b['date']` → `$b->date->translatedFormat('d M Y')`
   - `$b['time']` → `$b->start_time . ' – ' . $b->end_time`
   - `$b['category']` → read from `$b->logbook->category ?? '—'`
   - `$b['status']` → `$b->status`
   - `route('booking.show', 1)` → `route('booking.show', $b->id)`

3. Add pagination links below the table (replace placeholder):
   ```blade
   <div class="mt-4">
       {{ $bookings->links() }}
   </div>
   ```

4. Add filter logic: the status chips and search/date inputs should submit a
   GET form with `?status=` and `?date=` params. The controller reads these
   and filters the query. Wrap the filter bar in a `<form method="GET">`.

---

### STEP 5.9 — Wire Detail Page

**File:** `resources/views/booking/show.blade.php`

**Goal:** Replace the hardcoded `$booking` object with a real Eloquent model.

1. Remove the entire `@php ... $booking = (object)[...]; @endphp` block
   (lines 3–24).

2. Update attribute references:
   - `$booking->code` → `$booking->booking_code`
   - `$booking->type` → human-readable label from `$booking->booking_type`
   - `$booking->date` → `$booking->date->translatedFormat('d M Y')`
   - `$booking->day` → day-of-week derived from `$booking->date`
   - `$booking->start` → `$booking->start_time`
   - `$booking->end` → `$booking->end_time`
   - `$booking->duration` → computed from start/end
   - `$booking->computers` → `$booking->computers->pluck('label')->toArray()`
   - `$booking->category` → `$booking->logbook->category ?? null`
   - `$booking->reason` → `$booking->logbook->checkpoint_progress ?? null`
   - `$booking->submitted` → `$booking->submitted_at?->format('d M Y · H:i')`
   - `$booking->approved` → `$booking->reviewed_at?->format('d M Y · H:i')`
   - `$booking->logbook` → `$booking->logbook` (Eloquent relation)

3. Update `$canEditLogbook` and `$canCancel`:
   ```php
   @php
       $canEditLogbook = $booking->isEditable();
       $canCancel      = $booking->isCancellable();
   @endphp
   ```

4. Update the cancel button to POST to the correct route:
   ```blade
   <form method="POST" action="{{ route('booking.cancel', $booking) }}">
       @csrf
       <button type="submit" class="w-full btn-danger btn-sm justify-center">
           Batalkan Reservasi
       </button>
   </form>
   ```

5. Fix the logbook form action in `_logbook-form.blade.php`:
   - The form currently has `action="#"` — replace with `route('booking.logbook.update', $booking)`
   - The `$booking` variable must be available in the partial (pass it via `@include('booking._logbook-form', ['booking' => $booking])`)
   - Pre-fill fields with existing logbook values when editing:
     `value="{{ old('checkpoint_progress', $booking->logbook?->checkpoint_progress) }}"`

---

### STEP 5.10 — Wire Dashboard

**File:** `resources/views/dashboard.blade.php`

**Goal:** Replace hardcoded constants with real data. Make stat cards and
booking table accurate. Keep calendar JS structure intact — only feed it
real event data.

**Controller method `BookingController::dashboard()`:**

```php
public function dashboard(): View
{
    $user = auth()->user();

    $upcomingBookings = $user->bookings()
        ->with('computers')
        ->whereIn('status', ['submitted', 'under_review', 'approved'])
        ->where('date', '>=', today())
        ->orderBy('date')->orderBy('start_time')
        ->get();

    $completedBookings = $user->bookings()
        ->where('status', 'completed')
        ->latest('date')
        ->limit(5)
        ->get();

    $stats = [
        'upcoming_count'   => $upcomingBookings->count(),
        'this_month_total' => $user->bookings()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count(),
        'pending_count'    => $user->bookings()
            ->whereIn('status', ['submitted', 'under_review'])
            ->count(),
        'pending_code'     => $user->bookings()
            ->whereIn('status', ['submitted', 'under_review'])
            ->first()?->booking_code,
    ];

    // Calendar events for current month (for JS)
    $calendarEvents = $user->bookings()
        ->whereIn('status', ['submitted', 'under_review', 'approved'])
        ->whereMonth('date', now()->month)
        ->whereYear('date', now()->year)
        ->get(['date', 'start_time', 'end_time', 'booking_type'])
        ->groupBy(fn($b) => $b->date->day)
        ->map(fn($group) => $group->map(fn($b) => [
            Carbon::parse($b->start_time)->hour,
            Carbon::parse($b->end_time)->hour,
        ])->values());

    $computers = Computer::orderBy('unit_number')->get(['id', 'label', 'status']);

    return view('dashboard', compact(
        'upcomingBookings', 'completedBookings', 'stats', 'calendarEvents', 'computers'
    ));
}
```

**View changes (`dashboard.blade.php`):**
1. Replace stat card hardcoded numbers with `$stats['upcoming_count']`, etc.
2. Replace `const RESERVATIONS = { 12: [9,10,11], ... }` with:
   ```js
   const RESERVATIONS = @json($calendarEvents);
   ```
3. Replace `const TODAY = new Date(2026, 4, 8)` with today's actual date:
   ```js
   const TODAY = new Date({{ now()->year }}, {{ now()->month - 1 }}, {{ now()->day }});
   ```
4. Replace hardcoded booking table rows with a `@foreach` over `$upcomingBookings`.
5. Replace the computers section (dots grid + count) with real data from `$computers`.

---

### STEP 5.11 — AJAX Availability Endpoint

**Goal:** Replace the stub `checkAvailability()` in `schedule.blade.php` with
a real AJAX call.

**New controller:** `app/Http/Controllers/Api/AvailabilityController.php`

```php
// GET /api/check-availability?date=&start_time=&end_time=&type=&computers[]=
public function check(Request $request): JsonResponse
{
    $validated = $request->validate([
        'date'       => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time'   => 'required|date_format:H:i|after:start_time',
        'type'       => 'required|in:full_room,computers_only,room_only',
        'computers'  => 'array',
        'computers.*'=> 'integer|exists:computers,id',
        'room_sharing' => 'nullable|in:exclusive,shared',
    ]);

    $service  = new BookingService();
    $conflict = $service->checkConflict(
        $validated['date'],
        $validated['start_time'],
        $validated['end_time'],
        $validated['type'],
        $validated['computers'] ?? [],
        $validated['room_sharing'] ?? null,
    );

    return response()->json([
        'available' => !$conflict,
        'message'   => $conflict
            ? 'Slot ini sudah terpesan atau bertabrakan dengan reservasi lain.'
            : 'Slot tersedia.',
    ]);
}

// GET /api/computers/available?date=&start_time=&end_time=
public function availableComputers(Request $request): JsonResponse
{
    $validated = $request->validate([
        'date'       => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time'   => 'required|date_format:H:i',
    ]);

    $buffer = (int) LabSetting::get('buffer_minutes', 15);
    $active = ['submitted', 'under_review', 'approved'];

    $bookedComputerIds = Booking::where('date', $validated['date'])
        ->whereIn('status', $active)
        ->where('start_time', '<', Carbon::parse($validated['end_time'])->addMinutes($buffer)->format('H:i'))
        ->where('end_time', '>', Carbon::parse($validated['start_time'])->subMinutes($buffer)->format('H:i'))
        ->whereIn('booking_type', ['full_room', 'computers_only'])
        ->with('computers')
        ->get()
        ->flatMap(fn($b) => $b->computers->pluck('id'))
        ->unique()
        ->toArray();

    $computers = Computer::where('status', 'online')
        ->orderBy('unit_number')
        ->get(['id', 'unit_number', 'label', 'status'])
        ->map(fn($c) => [
            'id'        => $c->id,
            'label'     => $c->label,
            'available' => !in_array($c->id, $bookedComputerIds),
        ]);

    return response()->json(['computers' => $computers]);
}
```

**Update `schedule.blade.php`** — replace the stub `checkAvailability()` JS function
with a real `fetch()` call to `/api/check-availability`.

> Note: The JS call needs a CSRF token in the header for authenticated API routes.
> Add `<meta name="csrf-token" content="{{ csrf_token() }}">` to the layout
> (check if it exists first in `layouts/app.blade.php`).

---

## 11. ROUTE MAP (BEFORE → AFTER)

| Route | Before Phase 5 | After Phase 5 |
|-------|---------------|---------------|
| `GET /dashboard` | Closure → `view('dashboard')` | `BookingController::dashboard()` |
| `GET /booking/create` | Closure → redirect | Unchanged |
| `GET /booking/create/schedule` | Closure → `view('booking.schedule')` | `BookingController::showSchedule()` |
| `GET /booking/create/logbook` | Closure → `view('booking.logbook')` | `BookingController::showLogbook()` |
| `GET /booking/create/review` | Closure → `view('booking.review')` | `BookingController::showReview()` |
| `POST /booking` | **Does not exist** | `BookingController::store()` |
| `GET /booking/history` | Closure → `view('booking.history')` | `BookingController::history()` |
| `GET /booking/{id}` | Closure → `view('booking.show', ['id' => $id])` | `BookingController::show(Booking $booking)` |
| `POST /booking/{booking}/cancel` | **Does not exist** | `BookingController::cancel()` |
| `PUT /booking/{booking}/logbook` | **Does not exist** | `BookingLogbookController::update()` |
| `GET /api/check-availability` | **Does not exist** | `AvailabilityController::check()` |
| `GET /api/computers/available` | **Does not exist** | `AvailabilityController::availableComputers()` |

All admin routes remain as closures for now (Phase 6 will wire them).

---

## 12. SUCCESS CRITERIA

Phase 5 is complete when ALL of the following are true:

### Booking Creation Flow
- [ ] A logged-in user can visit `/booking/create/schedule`, select type + date + time + computers, and advance to logbook without error
- [ ] Selecting a Sunday or past date is prevented client-side (calendar JS already handles this) AND server-side (controller validates)
- [ ] Selecting a `computers_only` type without checking any computer box shows a validation error
- [ ] Logbook page shows no errors when arrived at from schedule with valid data
- [ ] Review page displays the actual data entered in steps 1 and 2 (not dummy data)
- [ ] Clicking "Kirim Permintaan" creates a `Booking` record in the DB with status `submitted`
- [ ] A `BookingLogbook` record is created alongside the `Booking`
- [ ] For `computers_only`, the `booking_computers` pivot is populated correctly
- [ ] The booking code follows the `LAB-NNNN` format and is unique
- [ ] After submission, the user is redirected to the booking detail page

### Conflict Detection
- [ ] Attempting to book a slot that overlaps an existing approved booking is rejected with a clear error message
- [ ] Two `computers_only` bookings on the same date/time with DIFFERENT computer units both succeed
- [ ] A `room_only` shared booking and a `computers_only` booking on the same slot both succeed
- [ ] A `full_room` booking blocks all subsequent booking attempts on that slot

### History & Detail Pages
- [ ] `/booking/history` shows the authenticated user's real bookings (not dummy data)
- [ ] Status filter chips actually filter the list
- [ ] Each row links to the correct `/booking/{id}` page
- [ ] `/booking/{id}` shows the real booking's details
- [ ] Accessing another user's booking ID returns a 403
- [ ] Logbook section is locked for `submitted`/`under_review`/`rejected`/`cancelled` bookings
- [ ] Logbook form appears for `approved`/`completed` bookings
- [ ] Cancel button is active for `approved` bookings; inactive for others
- [ ] Cancelling an approved booking changes its status to `cancelled` in the DB

### Logbook
- [ ] Submitting the logbook form saves a `BookingLogbook` record
- [ ] Submitting again updates the existing record (not duplicate insert)
- [ ] Logbook fields are pre-filled with existing values when editing

### Dashboard
- [ ] Stat cards show real counts from the DB
- [ ] Calendar dots appear on days with real bookings
- [ ] Upcoming and completed booking table rows come from the DB
- [ ] Computer status dots reflect real `computers.status` values

### AJAX Endpoints
- [ ] `GET /api/check-availability?date=...` returns `{"available": true/false}`
- [ ] `GET /api/computers/available?date=...` returns computer availability JSON
- [ ] The schedule page's availability indicator updates correctly after selecting a time slot

---

*This plan governs Phase 5 only. Do not implement any feature listed under Phase 6–9.*
*Once all success criteria above are checked, update `PROJECT-HANDOVER.md` to mark Phase 5 as COMPLETE.*
