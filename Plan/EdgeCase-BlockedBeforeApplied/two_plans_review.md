# Review: Two Booking Bug-Fix Plans

> Reviewed against the live codebase on 2026-05-19.

---

## Plan 1: PLAN-EXCLUSIVE-ROOM-BLOCKING-FIX.md

### Verdict: ✅ Already Implemented — Plan Can Be Archived

All three fixes (A, B, C) described in this plan have **already been applied** to the codebase:

| Fix | Status | Evidence |
|-----|--------|----------|
| **A-1**: `computers_only` branch checks `room_only`+`exclusive` | ✅ Done | [BookingService.php:65-71](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Services/BookingService.php#L65-L71) |
| **A-2**: `room_only`+`exclusive` checks `computers_only` | ✅ Done | [BookingService.php:90-93](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Services/BookingService.php#L90-L93) |
| **B**: `AvailabilityController` combines `full_room` and exclusive `room_only` | ✅ Done | [AvailabilityController.php:75-81](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Http/Controllers/Api/AvailabilityController.php#L75-L81) |
| **C**: Dashboard `$fullBlocks` includes exclusive `room_only` | ✅ Done | [BookingController.php:83,96-100](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Http/Controllers/BookingController.php#L83) |

The plan is sound and was correctly implemented. **No further action needed.**

---

## Plan 2: PLAN-SUBMITTED-BLOCKS-SLOT-FIX.md

### Verdict: ⚠️ Suitable Direction, But Has Issues That Need Addressing

The plan correctly identifies the core problem and chooses the right option (Option B). However, there are **3 critical issues**, **2 design concerns**, and **several improvements** possible.

---

### What the Plan Gets Right

1. **Problem diagnosis is spot-on.** The `ACTIVE_STATUSES = ['submitted', 'under_review', 'approved']` constant in `checkConflict()` causes submitted bookings to immediately hard-block slots for everyone else. This defeats the admin-approval workflow.

2. **Option B is the correct choice.** Only `approved` bookings should hard-block. Pending bookings should be visible but not blocking.

3. **Auto-reject on approve is essential.** When admin approves booking A, any competing pending booking B that now conflicts must be auto-rejected. The plan includes this correctly.

4. **Three-state calendar (free / pending / hard-booked)** is the right UX approach.

5. **The `AdminRequestController::approve()` already exists** and is the correct place for auto-reject logic.

---

### 🔴 Critical Issue 1: `checkConflict` Change Breaks `createBooking`

**The plan says:** Change `checkConflict()` to use only `['approved']` instead of `ACTIVE_STATUSES`.

**The problem:** `createBooking()` ([BookingService.php:120-127](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Services/BookingService.php#L120-L127)) also calls `checkConflict()` before inserting a new booking. After this change, `createBooking()` would only check against *approved* bookings — meaning **two users could submit the exact same `full_room` booking at the same time** and both succeed, which is fine for `computers_only` but dangerous for `full_room` and `room_only exclusive`.

**Why this matters for `full_room` / `exclusive`:** These types monopolize the entire room. If two pending `full_room` bookings exist for the same slot, admin can only approve one. But with the current plan, there's no cap — 50 users could all submit `full_room` for the same hour, creating an admin nightmare.

**Fix:** `checkConflict()` should **NOT be changed globally**. Instead, introduce **two modes**:

```php
// Option 1: Add a parameter
public function checkConflict(
    ...,
    bool $approvedOnly = false,  // NEW
): bool {
    $statuses = $approvedOnly
        ? ['approved']
        : self::ACTIVE_STATUSES;

    $base = Booking::query()
        ->whereIn('status', $statuses)
        ...
}
```

Then:
- `createBooking()` calls `checkConflict(..., approvedOnly: false)` — still checks all active statuses, preventing duplicate submissions of incompatible types.
- `AdminRequestController::show()` calls `checkConflict(..., approvedOnly: true)` — only flags conflicts with approved bookings.
- `AvailabilityController::check()` calls `checkConflict(..., approvedOnly: true)` — for the "soft-block" display.
- `AdminRequestController::approve()` keeps `approvedOnly: false` since it must verify no other *approved* booking has snuck in (using the existing exclude pattern).

> [!CAUTION]
> Without this fix, the system would allow unlimited competing `full_room` submissions for the same slot, overwhelming the admin.

---

### 🔴 Critical Issue 2: Auto-Reject Logic Is Outside the Transaction

**Plan Step 4** places the auto-reject loop **after** the existing `DB::transaction` in `approve()`:

```
// INSIDE the DB::transaction after $booking->update(['status' => 'approved', ...])
```

But looking at the current [AdminRequestController::approve()](file:///c:/xampp/htdocs/UKRIDA_LabReserve/app/Http/Controllers/Admin/AdminRequestController.php#L93-L129), the entire approve action is **already inside** a `DB::transaction`. The auto-reject must be **inside the same transaction**, not after it. If placed after, a race condition exists:

1. Admin approves booking A (transaction commits)
2. Before auto-reject runs, User B submits a conflicting booking
3. Auto-reject finds and rejects B
4. But B was submitted AFTER A was approved — it should have been rejected at submission time

**Fix:** Place the auto-reject loop inside the existing `DB::transaction` closure, right after the `$booking->update()` and `AuditLog::create()` calls:

```php
DB::transaction(function () use ($booking, $oldStatus) {
    // ... existing approve logic ...

    // Auto-reject conflicting pending bookings (INSIDE transaction)
    $this->autoRejectConflicting($booking);
});
```

---

### 🔴 Critical Issue 3: `checkConflict` Uses `lockForUpdate` — Calling It Per-Conflict Is Expensive

The plan's auto-reject loop calls `$this->bookings->checkConflict()` **for each** conflicting pending booking individually. But `checkConflict()` uses `lockForUpdate()`, which acquires row-level locks. Running it N times in a loop could:

1. Cause deadlocks if two admins approve simultaneously
2. Be unnecessarily slow with many pending bookings

**Better approach:** Since you've just approved booking A, you don't need to call `checkConflict()` for each pending booking B — you already know that **any pending booking that overlaps the same time window AND has an incompatible type** will conflict with the newly-approved A. You can express this as a single query:

```php
private function autoRejectConflicting(Booking $approved): void
{
    // Find all pending bookings that overlap the same time window
    $conflicting = Booking::where('id', '!=', $approved->id)
        ->whereIn('status', ['submitted', 'under_review'])
        ->where('date', $approved->date->format('Y-m-d'))
        ->where('start_time', '<', $approved->end_time)
        ->where('end_time', '>', $approved->start_time)
        ->with('computers:id')
        ->get();

    foreach ($conflicting as $pending) {
        // Check type compatibility (not all overlapping bookings conflict)
        if (!$this->typesConflict($approved, $pending)) {
            continue;
        }

        $pending->update([
            'status'      => 'rejected',
            'admin_notes' => 'Otomatis ditolak: reservasi '
                           . $approved->booking_code
                           . ' telah disetujui untuk slot yang sama.',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::create([...]);
    }
}

private function typesConflict(Booking $approved, Booking $pending): bool
{
    // full_room conflicts with everything
    if ($approved->booking_type === 'full_room') return true;
    if ($pending->booking_type === 'full_room') return true;

    // exclusive room_only conflicts with everything
    if ($approved->booking_type === 'room_only' && $approved->room_sharing === 'exclusive') return true;
    if ($pending->booking_type === 'room_only' && $pending->room_sharing === 'exclusive') return true;

    // computers_only vs computers_only: only if PCs overlap
    if ($approved->booking_type === 'computers_only' && $pending->booking_type === 'computers_only') {
        $approvedPcIds = $approved->computers->pluck('id');
        $pendingPcIds  = $pending->computers->pluck('id');
        return $approvedPcIds->intersect($pendingPcIds)->isNotEmpty();
    }

    // room_only shared + room_only shared: no conflict
    if ($approved->booking_type === 'room_only' && $approved->room_sharing === 'shared'
        && $pending->booking_type === 'room_only' && $pending->room_sharing === 'shared') {
        return false;
    }

    // computers_only + room_only shared: no conflict
    return false;
}
```

This avoids calling `checkConflict()` (with its `lockForUpdate()`) in a loop.

---

### 🟡 Design Concern 1: `AvailabilityController` Doubles Its Query Load

The plan adds a **second** `Booking::query()` for pending bookings alongside the existing one for approved bookings in `availableComputers()`. This doubles the DB queries per API call.

**Better approach:** Run a single query with `whereIn('status', ['submitted', 'under_review', 'approved'])` and split the results in PHP:

```php
$all = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->where('start_time', '<', $buffEnd)
    ->where('end_time', '>', $validated['start_time'])
    ->with('computers')
    ->get();

$approved = $all->where('status', 'approved');
$pending  = $all->whereIn('status', ['submitted', 'under_review']);
```

One query, same result, better performance.

---

### 🟡 Design Concern 2: Dashboard Now Requires Two Separate `$monthBookings` Queries

Same issue as above — the plan splits into `$approvedBookings` and `$pendingBookings` with two separate queries. Use a single query + in-memory partition:

```php
$monthBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date', now()->year)
    ->get(['id', 'date', 'start_time', 'end_time', 'booking_type', 'room_sharing', 'status']);

// Partition in-memory
$approvedMonth = $monthBookings->where('status', 'approved');
$pendingMonth  = $monthBookings->whereIn('status', ['submitted', 'under_review']);
```

---

### 🟢 Additional Improvements

#### I1 — Buffer Should Not Apply to Pending Display

The plan applies `buffer_minutes` to the pending overlap query in `availableComputers()`. This is wrong for display purposes — the buffer is a safety margin for the admin approval process. Users should see pending bookings at their **actual** time, not with an inflated buffer. Only the approved hard-block should use the buffer.

The plan actually notes `"no buffer needed for display"` in Step 2's pending query — good. But ensure this is consistently applied.

#### I2 — Self-Submitted Bookings Should Not Show as "Menunggu" to the Same User

If User A submits a booking, their own dashboard should show it as "Saya" (blue), not "Menunggu" (yellow). The plan doesn't distinguish between the current user's pending bookings and other users' pending bookings on the calendar.

**Fix:** In the dashboard view's JS, add a fourth state:
```js
const isMine = USER_EVENTS[day] && USER_EVENTS[day].includes(hourKey);
const softPending = !isMine && PENDING_BLOCKS[day] && PENDING_BLOCKS[day].includes(hourKey);
```

#### I3 — Schedule Page `check` Endpoint Should Also Show Soft-Pending Warning

The plan adds `pending: true` to `availableComputers()` response but **doesn't mention updating the `check()` endpoint response** for the schedule page's availability indicator. The plan does mention this in Step 2, but the schedule page JS (`runAvailabilityCheck()` in [schedule.blade.php:491-518](file:///c:/xampp/htdocs/UKRIDA_LabReserve/resources/views/booking/schedule.blade.php#L491-L518)) doesn't handle the `pending` field:

```js
// Current — only 'available' or 'conflict'
this.availStatus = data.available ? 'available' : 'conflict';
```

**Need to add:**
```js
this.availStatus = data.available
    ? (data.pending ? 'pending' : 'available')
    : 'conflict';
```

And a matching display state (yellow border, warning message).

#### I4 — Missing CSS for `slot-pending`

The plan includes CSS for `.cal-slot.slot-pending` but this class isn't defined in the existing [dashboard.blade.php](file:///c:/xampp/htdocs/UKRIDA_LabReserve/resources/views/dashboard.blade.php#L39) CSS block. It needs to be added alongside the existing `.slot-booked` and `.slot-mine` styles.

#### I5 — `AdminRequestController::show()` Conflict Check Semantics Change

Currently, `show()` calls `checkConflict()` with `ACTIVE_STATUSES` (which includes pending). After this fix, if you only check against `approved`, the conflict flag becomes **meaningful for admin**: "This booking would conflict with an already-approved booking." 

But the plan doesn't explicitly mention changing the `show()` call. With the `approvedOnly` parameter approach from Critical Issue 1, you'd call:

```php
$hasConflict = DB::transaction(fn () => $this->bookings->checkConflict(
    ...,
    approvedOnly: true,  // Only flag conflicts with APPROVED bookings
));
```

This way, admin only sees a red conflict flag when it actually matters (an approved booking blocks this one), not when two pending bookings happen to overlap (which is now intentionally allowed).

---

## Summary Matrix

### Plan 1: Exclusive Room Blocking Fix
| Aspect | Status |
|--------|--------|
| Correctness | ✅ Fully correct |
| Implementation | ✅ Already applied |
| Action needed | Archive the plan |

### Plan 2: Submitted Blocks Slot Fix
| Category | Count | Severity |
|----------|-------|----------|
| Critical issues | 3 | 🔴 Must fix before implementation |
| Design concerns | 2 | 🟡 Should optimize |
| Additional improvements | 5 | 🟢 Recommended |

### Recommended Changes to Plan 2 Before Implementation

> [!IMPORTANT]
> **Top 3 must-fix items:**
> 1. **Don't change `checkConflict()` globally** — add an `approvedOnly` parameter so `createBooking()` still prevents duplicate incompatible submissions
> 2. **Auto-reject must be inside the same DB transaction** as the approve — not after it
> 3. **Use a dedicated `typesConflict()` method** instead of calling `checkConflict()` per-conflict in a loop — avoids deadlocks and is faster
