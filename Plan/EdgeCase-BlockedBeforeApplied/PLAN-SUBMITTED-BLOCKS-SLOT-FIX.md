# PLAN: Submitted Bookings Should Not Hard-Block Slots Before Admin Approval

## Problem Statement

When User A submits a booking request (status = `submitted`), the slot is
**immediately hard-blocked** for every other user — before an admin has reviewed
or approved anything. User B logs in with a different account, sees the slot as
unavailable, and cannot submit their own request for that time.

The system has a deliberate admin-approval workflow
(`submitted → under_review → approved / rejected`), but that workflow is
rendered ineffective for competing requests: only one user can ever submit for a
given slot, so admin only ever sees a single request per slot and can only
approve or reject it — they cannot compare competing requests and choose the
best one.

---

## Root Cause

`BookingService::ACTIVE_STATUSES` includes `submitted` and `under_review`:

```php
// app/Services/BookingService.php
private const ACTIVE_STATUSES = ['submitted', 'under_review', 'approved'];
```

This constant is used in **every** availability / conflict check:

| Location | Effect |
|---|---|
| `BookingService::checkConflict()` | `whereIn('status', ACTIVE_STATUSES)` → new booking POST rejected |
| `AvailabilityController::check()` | same → availability API returns `false` |
| `AvailabilityController::availableComputers()` | same → PC grid shows all PCs as "Terpakai" |
| `BookingController::dashboard()` `$fullBlocks` | same → calendar slot turns dark/unclickable |

As soon as a user hits **Submit**, all four layers treat the slot as taken.

---

## Downstream Problems

### 1 — Slot squatting / no-shows
A user submits a request they later decide not to attend but forget to cancel.
The slot is blocked for everyone else until admin manually rejects it. If admin
review is slow (days), the slot is wasted.

### 2 — Admin loses discretion
Because only one user can ever submit for a slot, admin's role is reduced to
rubber-stamping a single request rather than comparing competing ones. Priority
bookings (e.g., thesis work over casual projects) can never be preferred because
lower-priority requests block the slot first.

### 3 — First-to-click wins, not first-to-deserve
Submission speed, not merit or priority, determines who gets a slot.

### 4 — Misleading calendar / PC grid
Other users see the slot as fully blocked. There is no visual distinction
between "pending request — might still become available" and "admin-confirmed —
definitely taken". Users may give up on a slot that admin ultimately rejects.

### 5 — Admin conflict-check inconsistency
`AdminRequestController::show()` runs `checkConflict(excludeBookingId: $booking->id)`
when admin opens a pending request to check for live conflicts. That check still
uses `ACTIVE_STATUSES`, so if two pending bookings were somehow submitted for
the same slot (e.g., race condition), admin sees a conflict flag even though
neither is approved yet.

---

## Fix Options

### Option A — Visual distinction only *(minimal change, keeps blocking)*

Keep `submitted` in `ACTIVE_STATUSES`. Change the calendar and PC grid to show
a separate "Pending" state (e.g., yellow/orange) instead of the same red/dark
"Booked" state, so users understand the slot has a pending request but is not
yet confirmed.

**Pro:** Smallest change (CSS + template only). No logic change.  
**Con:** Does not fix any of the downstream problems. Admin still can't compare
competing requests. Slot squatting still possible.

---

### Option B — Approval-gated blocking + auto-reject on approve *(Recommended)*

Change the system so that **only `approved` bookings hard-block slots**.
`submitted` and `under_review` become *soft-pending* — visible on calendars
and PCs as "Menunggu" but still clickable / bookable by others.

When admin approves a booking, any other pending bookings that conflict with it
are **automatically rejected** (with a note that a conflicting request was
approved).

This makes the admin-approval step meaningful: multiple users can compete for
the same slot, and admin picks the winner.

**Pro:** Fixes all five downstream problems. Matches the spirit of the approval
workflow. Admins have real discretion.  
**Con:** Requires changes across 5 files + 1 new query in the approve action.
Increases admin workload slightly (they may see more pending requests per slot).

---

### Option C — Submission cap per slot *(middle ground)*

Allow at most N (e.g., 3) pending requests per slot. Once the cap is reached,
further submissions are blocked. Admin sees the competing requests and picks one.

**Pro:** Prevents spam while still giving admin some choice.  
**Con:** Arbitrary cap. Still doesn't fix slot squatting for the first N
submitters. More complex than Option B.

---

## Chosen Fix: Option B

### Status Semantics After Fix

| Status | Blocks slot for others? | Visible on calendar/grid? |
|---|---|---|
| `submitted` | No | Yes — "Menunggu" (soft) |
| `under_review` | No | Yes — "Menunggu" (soft) |
| `approved` | **Yes** | Yes — "Terpesan" (hard) |
| `rejected` / `cancelled` / `completed` | No | No |

---

## Files to Change

| File | Change |
|---|---|
| `app/Services/BookingService.php` | New constant `PENDING_STATUSES`; conflict check uses only `approved`; keep `ACTIVE_STATUSES` for admin count helpers |
| `app/Http/Controllers/Api/AvailabilityController.php` | `check()` and `availableComputers()` — use `approved` only for hard-block; add soft-pending flag to response |
| `app/Http/Controllers/BookingController.php` | `dashboard()` — split `$fullBlocks` (approved only) from `$pendingBlocks` (submitted+under_review); pass both to view |
| `app/Http/Controllers/Admin/AdminRequestController.php` | `approve()` — after setting status=approved, auto-reject all conflicting pending bookings |
| `resources/views/dashboard.blade.php` | Calendar rendering — three states: free / pending / hard-booked |

---

## Step-by-Step Implementation

### Step 1 — `BookingService.php`: split status constants

```php
// BEFORE
private const ACTIVE_STATUSES = ['submitted', 'under_review', 'approved'];

// AFTER
private const PENDING_STATUSES  = ['submitted', 'under_review'];
private const APPROVED_STATUSES = ['approved'];
private const ACTIVE_STATUSES   = ['submitted', 'under_review', 'approved']; // kept for admin helpers only
```

Change `checkConflict()` base query to use only `APPROVED_STATUSES`:

```php
// BEFORE
->whereIn('status', self::ACTIVE_STATUSES)

// AFTER
->whereIn('status', self::APPROVED_STATUSES)
```

This means the conflict check now only fires when an **approved** booking exists
at that slot. Two pending requests for the same slot no longer conflict with
each other at submission time.

### Step 2 — `AvailabilityController.php`: soft-block awareness

**`check()` endpoint** — change `whereIn('status', [...])` to `approved` only.
Also return a `pending` flag so the frontend can show a softer warning.

```php
// In check():
$conflict = DB::transaction(fn () => $this->bookings->checkConflict(...));

// NEW: check for pending (soft) bookings at the same slot
$hasPending = Booking::where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('start_time', '<', $validated['end_time'])
    ->where('end_time',   '>', $validated['start_time'])
    ->exists();

return response()->json([
    'available' => ! $conflict,
    'pending'   => ! $conflict && $hasPending,
    'message'   => $conflict
        ? 'Slot ini sudah disetujui untuk pengguna lain.'
        : ($hasPending
            ? 'Slot ini sedang dalam proses persetujuan oleh pengguna lain. Anda tetap dapat mengajukan permintaan.'
            : 'Slot tersedia.'),
]);
```

**`availableComputers()` endpoint** — change overlap query to `approved` only.
Add a separate soft-pending query to mark PCs that have a pending (not yet
approved) claim, and return them as `pending: true` in the JSON so the schedule
page can show a "Menunggu" badge instead of "Terpakai".

```php
// Hard-blocked: approved bookings only
$overlapping = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['approved'])                      // ← changed
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $validated['start_time'])
    ->with('computers')
    ->get();

// Soft-pending: submitted/under_review (no buffer needed for display)
$pendingOverlapping = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('start_time', '<', $validated['end_time'])
    ->where('end_time',   '>', $validated['start_time'])
    ->with('computers')
    ->get();

// ... existing $bookedIds logic (approved only) ...

$pendingIds = $hasPendingFullBlock
    ? Computer::pluck('id')->toArray()
    : $pendingOverlapping
        ->where('booking_type', 'computers_only')
        ->flatMap(fn ($b) => $b->computers->pluck('id'))
        ->unique()->values()->toArray();

$computers = Computer::orderBy('unit_number')
    ->get(['id', 'unit_number', 'label', 'status'])
    ->map(fn ($c) => [
        'id'        => $c->id,
        'label'     => $c->label,
        'status'    => $c->status,
        'available' => $c->status === 'online' && ! in_array($c->id, $bookedIds, true),
        'pending'   => $c->status === 'online'
                       && ! in_array($c->id, $bookedIds, true)
                       && in_array($c->id, $pendingIds, true),
    ]);
```

### Step 3 — `BookingController.php` `dashboard()`: split fullBlocks into two layers

```php
// BEFORE (single $monthBookings query using submitted+under_review+approved)
$monthBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ...

// AFTER: approved bookings → $fullBlocks (hard, calendar turns dark)
$approvedBookings = Booking::with('computers:id')
    ->whereIn('status', ['approved'])
    ...

// + pending bookings → $pendingBlocks (soft, calendar shows yellow)
$pendingBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review'])
    ...
```

Run the same hour-range grouping logic for both to produce:
- `$fullBlocks`   — hours blocked by approved bookings (non-clickable, dark)
- `$pendingBlocks` — hours with pending requests (clickable but shows warning dot)

Pass both to the view.

### Step 4 — `AdminRequestController.php` `approve()`: auto-reject conflicting pending bookings

After the existing approve transaction succeeds, add a second query that finds
all other `submitted` / `under_review` bookings that conflict with the
just-approved booking, and rejects them automatically:

```php
// INSIDE the DB::transaction after $booking->update(['status' => 'approved', ...])

// Auto-reject pending bookings that now conflict with the newly approved one.
$conflictingPending = Booking::where('id', '!=', $booking->id)
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('date', $booking->date->format('Y-m-d'))
    ->where('start_time', '<', $booking->end_time)
    ->where('end_time',   '>', $booking->start_time)
    ->get();

foreach ($conflictingPending as $conflict) {
    // Only auto-reject if this booking genuinely conflicts (use full type logic).
    $stillConflicts = $this->bookings->checkConflict(
        date:             $booking->date->format('Y-m-d'),
        startTime:        substr((string) $conflict->start_time, 0, 5),
        endTime:          substr((string) $conflict->end_time, 0, 5),
        bookingType:      $conflict->booking_type,
        computerIds:      $conflict->computers->pluck('id')->toArray(),
        roomSharing:      $conflict->room_sharing,
        excludeBookingId: $conflict->id,
    );

    if ($stillConflicts) {
        $conflict->update([
            'status'      => 'rejected',
            'admin_notes' => 'Otomatis ditolak karena reservasi ' . $booking->booking_code . ' telah disetujui untuk slot yang sama.',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => 'booking.auto_rejected',
            'auditable_type' => Booking::class,
            'auditable_id'   => $conflict->id,
            'old_values'     => ['status' => $conflict->getOriginal('status')],
            'new_values'     => ['status' => 'rejected'],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
```

### Step 5 — `dashboard.blade.php`: three calendar slot states

Extend `renderTimeSlots()` to handle three visual states:

```js
const FULL_BLOCKS    = @json($fullBlocks);    // approved — hard block
const PENDING_BLOCKS = @json($pendingBlocks); // submitted/under_review — soft
const USER_EVENTS    = @json($userEvents);

// In renderTimeSlots():
const hardBlocked    = FULL_BLOCKS[day]    && FULL_BLOCKS[day].includes(hourKey);
const softPending    = PENDING_BLOCKS[day] && PENDING_BLOCKS[day].includes(hourKey);
const hasUserEvent   = USER_EVENTS[day]    && USER_EVENTS[day].includes(hourKey);

el.className = 'cal-slot'
    + (hardBlocked ? ' slot-booked'   : '')
    + (!hardBlocked && softPending  ? ' slot-pending' : '')
    + (!hardBlocked && hasUserEvent ? ' slot-mine'    : '');

// Hard-blocked: no click. Soft-pending: allow click but show warning modal.
if (!hardBlocked) el.addEventListener('click', () => openSlotModal(day, slot));
```

Add CSS:

```css
.cal-slot.slot-pending {
    background: #FFFBEB;
    border-color: #FDE68A;
    color: #D97706;
}
```

The slot modal can show a yellow warning banner when `softPending` is true:
*"Ada permintaan yang sedang ditinjau untuk slot ini. Anda tetap dapat mengajukan permintaan."*

### Step 6 — Schedule page PC grid: show `pending` badge

In `booking/schedule.blade.php`, the `getPcState()` Alpine function currently
returns `booked` or `available`. Add a `pending` state using the new
`pending: true` field from the API:

```js
getPcState(pcId) {
    if (!this.pcAvailability[pcId]) return 'unknown';
    const pc = this.pcAvailability[pcId];
    if (!pc.available) return 'booked';
    if (pc.pending)    return 'pending';
    return 'available';
}
```

Badge labels: `available` → "Tersedia" (green), `pending` → "Menunggu" (yellow),
`booked` → "Terpakai" (red/grey).

---

## PHP Lint Checklist

```bash
php -l app/Services/BookingService.php
php -l app/Http/Controllers/Api/AvailabilityController.php
php -l app/Http/Controllers/BookingController.php
php -l app/Http/Controllers/Admin/AdminRequestController.php
```

---

## Manual Test Scenarios

| Scenario | Expected result |
|---|---|
| User A submits. User B views same slot on calendar. | Slot shows "Menunggu" (yellow), still clickable |
| User A submits. User B tries to submit same slot. | Allowed — both appear in admin queue |
| Admin approves User A. | User B's request auto-rejected with note |
| User A submits then cancels. Slot immediately reopens for others. | Yes — no approved booking blocking |
| User A submits `approved` booking (from admin). Slot hard-blocked. | Yes — calendar dark, not clickable |
| Admin opens a pending booking detail page → `hasConflict` flag | Only fires against OTHER *approved* bookings, not other pending |

---

## Risk Assessment

**Medium risk.** This is a behavioural change to the core booking contract.
The main risks are:

1. **Race condition at store time**: two users submit simultaneously for the
   same slot, both pass `checkConflict()` (which now only checks approved), and
   both get `submitted` status. This is intentional — admin reviews both and
   approves one. The auto-reject logic in `approve()` cleans up the loser.
   However, `createBooking()` should still call `checkConflict()` before
   inserting, so if one is approved before the second arrives, the second is
   immediately rejected at submission time.

2. **Admin workload**: admin may now see multiple pending requests per slot
   and must consciously choose one. This is a feature, not a bug — but the
   admin UI should surface conflicts clearly (the existing `$hasConflict` flag
   helps here).

No schema changes needed. No migration needed.
