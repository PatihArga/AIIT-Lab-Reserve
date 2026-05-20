# REVIEW: Room-Sharing Conflict Display Fix

**Reviewer:** AI Assistant  
**Date:** 2026-05-20  
**Plan reviewed:** `PLAN-ROOM-SHARING-CONFLICT-FIX.md`  
**Status:** APPROVED with refinements

---

## Files Verified Against Plan

| File | Lines Read | Plan References Accurate? |
|------|-----------|--------------------------|
| `app/Services/BookingService.php` | 1–266 (full) | ✅ Yes |
| `app/Http/Controllers/BookingController.php` | 1–412 (full) | ✅ Yes |
| `app/Http/Controllers/Api/AvailabilityController.php` | 1–138 (full) | ✅ Yes |

---

## Verdict

**The plan is solid and ready to implement**, with three refinements recommended below.

---

## What's Correct

### 1. Root Cause Analysis — Bug 1 (Calendar Invisibility)

Verified in `BookingController.php` lines 77–119. The `$computeHourBlocks` closure marks an hour as blocked only when:

- A `full_room` booking covers that hour, **or**
- A `room_only + exclusive` booking covers that hour, **or**
- `computers_only` bookings consume **all** online PC units for that hour.

`room_only + shared` bookings meet **none** of these criteria:
- `$hasFullRoom = false` (not `full_room`)
- `$hasExclusiveRoom = false` (only matches `exclusive`, not `shared`)
- Not included in `$bookedPcIds` (only `computers_only` is filtered)

**Result:** Approved `room_only + shared` bookings produce zero entries in `$fullBlocks`. The calendar shows green/free for slots that are actually partially occupied. Confirmed.

### 2. Root Cause Analysis — Bug 2 (`hasPending` Type-Agnostic)

Verified in `AvailabilityController.php` lines 47–51. The `$hasPending` query finds **any** pending booking regardless of type. Combined with:

- Display API: `approvedOnly: true` → only approved count as hard conflicts
- `createBooking()`: `approvedOnly: false` (default) → pending also block

This creates misleading responses:

| New type | Existing pending | API says | `createBooking()` does |
|----------|-----------------|----------|----------------------|
| `full_room` | `room_only + shared` (pending) | `available: true, pending: true` ("you can submit") | **REJECTS** |
| `room_only + exclusive` | `room_only + shared` (pending) | `available: true, pending: true` | **REJECTS** |
| `room_only + shared` | `computers_only` (pending) | `available: true, pending: true` | **SUCCEEDS** |

The first two rows are the problem — user is invited to submit but server rejects.

### 3. Conflict Logic Is Correct — No Changes Needed

Verified `BookingService::checkConflict()` (lines 42–116) and `typesConflict()` (lines 238–264). The conflict matrix is sound:

| Type A | Type B | Conflict? |
|--------|--------|-----------|
| `full_room` | anything | ✅ Yes |
| `room_only + exclusive` | anything | ✅ Yes |
| `room_only + shared` | `room_only + shared` | ❌ No (compatible) |
| `room_only + shared` | `computers_only` | ❌ No (compatible) |
| `room_only + shared` | `room_only + exclusive` | ✅ Yes |
| `computers_only` | `computers_only` | Only if PCs overlap |

The plan correctly leaves this untouched.

### 4. Fix 1 — Calendar `$sharedRoomBlocks`

The approach is additive and low-risk. Computing a new collection from `$approvedMonth` and adding a fifth visual state to the calendar is clean. The CSS choice (teal) is distinct from existing states (red = hard-blocked, yellow = pending, blue = mine, green = free).

### 5. Fix 2 — Type-Aware `$pendingConflict`

Reusing `checkConflict()` with `approvedOnly: false` to mirror `createBooking()` is the correct approach. This eliminates the API/submission mismatch entirely.

---

## Recommended Refinements

### Refinement 1: Include Pending Shared-Room Bookings on Calendar

> [!IMPORTANT]
> The plan only computes `$sharedRoomBlocks` from `$approvedMonth`. But a **pending** `room_only + shared` booking also partially occupies the room.

If a pending `room_only + shared` booking exists, it's currently invisible on the calendar:
- Not in `$fullBlocks` (not a hard block)
- Not in `$pendingBlocks` (the closure skips shared-room)
- Not in `$sharedRoomBlocks` (plan only uses approved)

**Options:**

1. **Compute `$pendingSharedRoomBlocks`** separately and pass to the view — gives the JS two distinct datasets to style differently (teal solid for approved, teal striped/dashed for pending).

2. **Merge pending + approved shared-room bookings** into one `$sharedRoomBlocks` collection — simpler, but loses the approved/pending distinction.

**Recommendation:** Option 1 is more informative but adds complexity. Option 2 is simpler and still solves the core problem (visibility). Go with Option 2 unless you want granular styling.

If Option 2, change the computation to:

```php
$sharedRoomBlocks = $monthBookings   // ← all statuses, not just $approvedMonth
    ->filter(fn ($b) => $b->booking_type === 'room_only' && $b->room_sharing === 'shared')
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

---

### Refinement 2: Combine Two DB Transactions in Availability API

> [!TIP]
> The plan adds a **second** `DB::transaction()` + `lockForUpdate()` call. Under high load, two separate transactions with row locks per API request is unnecessary overhead.

Combine into a single transaction:

```php
[$conflict, $pendingConflict] = DB::transaction(function () use ($validated) {
    $hard = $this->bookings->checkConflict(
        date:        $validated['date'],
        startTime:   $validated['start_time'],
        endTime:     $validated['end_time'],
        bookingType: $validated['type'],
        computerIds: $validated['computers'] ?? [],
        roomSharing: $validated['room_sharing'] ?? null,
        approvedOnly: true,
    );

    $soft = !$hard && $this->bookings->checkConflict(
        date:        $validated['date'],
        startTime:   $validated['start_time'],
        endTime:     $validated['end_time'],
        bookingType: $validated['type'],
        computerIds: $validated['computers'] ?? [],
        roomSharing: $validated['room_sharing'] ?? null,
        approvedOnly: false,
    );

    return [$hard, $soft];
});
```

This runs both checks under one lock, is slightly faster, and avoids a TOCTOU gap between the two transactions.

---

### Refinement 3: Disable Incompatible Booking Types in Modal

> [!NOTE]
> The plan describes an informational banner in the slot modal but doesn't specify whether incompatible booking types should be **disabled** or just warned.

**Recommendation:** When a slot has `sharedRoom = true`, the modal's booking type selector should:

1. **Disable** "Ruang + Komputer (Full Room)" and "Ruang Saja — Eksklusif" options
2. Show a tooltip/subtitle: *"Tidak tersedia — ruangan sedang digunakan berbagi"*
3. Keep "Komputer Saja" and "Ruang Saja — Berbagi" enabled

This prevents the user from even reaching the schedule page with an incompatible type, providing a better UX than allowing submission and showing a server error.

---

## Risk Assessment

| Change | Risk | Notes |
|--------|------|-------|
| `$sharedRoomBlocks` in controller | **Low** | Additive; new variable only |
| Calendar JS + CSS | **Low** | New rendering branch; existing branches unchanged |
| `$pendingConflict` in availability API | **Medium** | Changes `available` response from `true` to `false` for incompatible-pending combinations — verify schedule page JS handles this correctly |
| `compact()` update | **Low** | Additive |

---

## Implementation Scope

| Item | Complexity | Ready? |
|------|-----------|--------|
| `$sharedRoomBlocks` in `BookingController::dashboard()` | Low | ✅ Yes |
| `SHARED_ROOM_BLOCKS` JS constant + `.slot-shared` CSS | Low | ✅ Yes |
| `renderTimeSlots()` JS update (5th calendar state) | Medium | ✅ Yes |
| Modal banner + disabled types for shared-room slots | Medium | ✅ Yes |
| `$pendingConflict` in `AvailabilityController::check()` | Medium | ✅ Yes |
| Updated response messages (Indonesian) | Low | ✅ Yes |

**Total files changed: 3.** No new files, no model changes, no migration changes.

---

## Summary

The plan correctly identifies both bugs and proposes the right fixes. The three refinements above are recommended before implementation:

1. **Include pending shared-room bookings** in calendar display (not just approved)
2. **Combine the two DB transactions** in the availability API into one
3. **Disable incompatible booking types** in the modal (not just warn)

All three refinements are minor additions that don't change the plan's architecture — they polish it.
