# PLAN: Room-Sharing Conflict Display Fix

**Branch:** `CoreBookingBackEnd`  
**Date:** 2026-05-20  
**Status:** DRAFT — awaiting review

---

## Problem Statement

User reports:

> "When I reserve a full lab with sharing and approve it, other users can reserve rooms and rooms + computers. However, if a room is already reserved, they can no longer reserve rooms. Only computers can be reserved."

Interpreted as:
1. `room_only + shared` is approved by admin.
2. Other users trying `room_only + shared` (compatible) → **allowed** ✓
3. Other users trying `full_room` or `room_only + exclusive` → **blocked**, but calendar shows slot as fully green with no warning.
4. Once a *second* `room_only` booking exists in the system (pending or approved), new `room_only` exclusive requests are still blocked — which is correct, but the user sees no explanation and the UX gives no upfront signal that the room is partially occupied.

---

## Root-Cause Analysis

### Bug 1 — `room_only + shared` is invisible on the dashboard calendar

**File:** `app/Http/Controllers/BookingController.php` — `$computeHourBlocks` closure (lines 77–119)

The closure marks an hour as blocked only when:
- A `full_room` booking covers that hour, **or**
- A `room_only + exclusive` booking covers that hour, **or**
- `computers_only` bookings consume all online PC units for that hour.

`room_only + shared` bookings meet none of these criteria. They are `booking_type = 'room_only'` with `room_sharing = 'shared'`, so:
- `$hasFullRoom = false`
- `$hasExclusiveRoom = false` (only matches `exclusive`)
- Not included in `$bookedPcIds` (only `computers_only` is filtered here)

**Result:** Hours occupied by an approved `room_only + shared` booking produce **zero entries** in `$fullBlocks`. The calendar slot is green, non-blocked, and clickable — even though `full_room` and `room_only + exclusive` are actually unavailable for that slot.

The same closure applied to `$pendingMonth` means **pending** `room_only + shared` bookings are also invisible in `$pendingBlocks`.

### Bug 2 — `hasPending` in AvailabilityController is type-agnostic

**File:** `app/Http/Controllers/Api/AvailabilityController.php` — `check()` method (lines 47–51)

```php
$hasPending = ! $conflict && Booking::where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('start_time', '<', $validated['end_time'])
    ->where('end_time',   '>', $validated['start_time'])
    ->exists();
```

This query finds **any** pending booking regardless of its type. Combined with:
- The display API using `approvedOnly: true` → only approved bookings count as hard conflicts.
- `createBooking()` using `approvedOnly: false` (default) → pending bookings also block incompatible types.

This creates a gap:

| New type | Existing pending | API response | createBooking result |
|---|---|---|---|
| `full_room` | `room_only + shared` (pending) | `available: true, pending: true` (amber, "you can still submit") | **FAILS** — pending shared blocks full_room |
| `room_only + exclusive` | `room_only + shared` (pending) | `available: true, pending: true` | **FAILS** — pending shared blocks exclusive |
| `room_only + shared` | `computers_only` (pending) | `available: true, pending: true` | **SUCCEEDS** — compatible, will compete |

The `pending` flag with its message "Anda tetap dapat mengajukan permintaan" ("you can still submit") is misleading when the combination is incompatible. The user is invited to submit but the server rejects it.

---

## Impact Matrix

| Booking type user tries | Approved `room_only + shared` exists | Expected | Actual conflict check | Calendar shows |
|---|---|---|---|---|
| `room_only + shared` | YES | Allowed | Correct ✓ | Free (green) — no competing indicator |
| `computers_only` | YES | Allowed | Correct ✓ | Free (green) — OK for this type |
| `full_room` | YES | Blocked | Correct ✓ | Free (green) — **misleading** |
| `room_only + exclusive` | YES | Blocked | Correct ✓ | Free (green) — **misleading** |

---

## Files to Change

| File | Change |
|---|---|
| `app/Http/Controllers/BookingController.php` | Add `$sharedRoomBlocks` collection and pass to view |
| `resources/views/dashboard.blade.php` | Add new "shared occupancy" calendar state (teal/purple border) |
| `app/Http/Controllers/Api/AvailabilityController.php` | Make `hasPending` type-aware |

---

## Fix 1 — Add Shared Occupancy to Calendar

### 1a. `BookingController::dashboard()` — add `$sharedRoomBlocks`

After the existing `$fullBlocks = $computeHourBlocks($approvedMonth)` and `$pendingBlocks = $computeHourBlocks($pendingMonth)` lines, add:

```php
// sharedRoomBlocks: hours where an approved room_only+shared booking exists.
// These hours are NOT hard-blocked (computers_only and room_only+shared can still be booked)
// but the calendar should indicate partial room occupancy so users know exclusive/full_room won't work.
$sharedRoomBlocks = $approvedMonth
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

Also add `$sharedRoomBlocks` to the `compact()` call:

```php
return view('dashboard', compact(
    'upcomingBookings', 'completedBookings', 'stats',
    'fullBlocks', 'pendingBlocks', 'sharedRoomBlocks', 'userEvents'  // ← add sharedRoomBlocks
));
```

### 1b. `resources/views/dashboard.blade.php` — new JS constant and calendar rendering

**Add the constant** (near the existing `FULL_BLOCKS` and `PENDING_BLOCKS` declarations):

```js
const SHARED_ROOM_BLOCKS = @json($sharedRoomBlocks);
```

**Update `renderTimeSlots()`** to add a fifth state with priority:

```
hardBlocked > isMine > softPending > sharedRoom > free
```

The `sharedRoom` state means: the room is shared-occupied, but the slot is still available for `computers_only` or another `room_only + shared`. The cell should be clickable but show a visual indicator.

```js
const hardBlocked  = FULL_BLOCKS[day]         && FULL_BLOCKS[day].includes(hourKey);
const isMine       = USER_EVENTS[day]         && USER_EVENTS[day].includes(hourKey);
const softPending  = !isMine && PENDING_BLOCKS[day]     && PENDING_BLOCKS[day].includes(hourKey);
const sharedRoom   = !isMine && !softPending && SHARED_ROOM_BLOCKS[day] && SHARED_ROOM_BLOCKS[day].includes(hourKey);
```

**Cell CSS class** for `sharedRoom` state (teal, to distinguish from yellow pending and red hard-blocked):

```css
.cal-slot.slot-shared {
    background: #F0FDFA;
    border-color: #99F6E4;
    color: #0D9488;
    cursor: pointer;
}
.cal-slot.slot-shared:hover {
    background: #CCFBF1;
    border-color: #2DD4BF;
}
```

**Cell label:** "Ruang Tersewa" (room is rented/shared).

**Click behaviour:** Opens the slot modal as normal (slot is clickable — user can still book compatible types). Optionally pass an `opts.sharedRoom = true` flag to `openSlotModal()` to show an informational banner:

```
ℹ️  Ruangan pada slot ini sedang digunakan secara berbagi. 
Reservasi Komputer Saja atau Ruang Saja (Berbagi) masih tersedia.
Ruang + Komputer dan Ruang Eksklusif tidak tersedia.
```

---

## Fix 2 — Type-aware `hasPending` in AvailabilityController

**File:** `app/Http/Controllers/Api/AvailabilityController.php` — `check()` method

Replace the current type-agnostic `hasPending` block with a type-aware check that mirrors `checkConflict()` but only for pending statuses.

**Current code (lines 47–60):**

```php
$hasPending = ! $conflict && Booking::where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('start_time', '<', $validated['end_time'])
    ->where('end_time',   '>', $validated['start_time'])
    ->exists();

return response()->json([
    'available' => ! $conflict,
    'pending'   => $hasPending,
    'message'   => $conflict
        ? 'Slot ini sudah disetujui untuk pengguna lain.'
        : ($hasPending
            ? 'Ada permintaan yang sedang ditinjau untuk slot ini. Anda tetap dapat mengajukan permintaan.'
            : 'Slot tersedia.'),
]);
```

**Replacement:**

```php
// Only show "pending" when there's a type-COMPATIBLE competing request.
// An incompatible pending booking blocks submission at createBooking() level (approvedOnly=false),
// so we must report it as a conflict rather than a soft warning.
$pendingConflict = ! $conflict && DB::transaction(fn () => $this->bookings->checkConflict(
    date:        $validated['date'],
    startTime:   $validated['start_time'],
    endTime:     $validated['end_time'],
    bookingType: $validated['type'],
    computerIds: $validated['computers'] ?? [],
    roomSharing: $validated['room_sharing'] ?? null,
    approvedOnly: false,   // includes pending statuses — mirrors createBooking()
));

// hasPending = compatible pending bookings exist (actual safe competition for admin to decide)
$hasPending = ! $conflict && ! $pendingConflict && Booking::where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review'])
    ->where('start_time', '<', $validated['end_time'])
    ->where('end_time',   '>', $validated['start_time'])
    ->exists();

return response()->json([
    'available' => ! $conflict && ! $pendingConflict,
    'pending'   => $hasPending,
    'message'   => ($conflict || $pendingConflict)
        ? 'Slot ini tidak tersedia — sudah ada reservasi yang disetujui atau permintaan yang tidak kompatibel.'
        : ($hasPending
            ? 'Ada permintaan yang sedang ditinjau untuk slot ini. Anda tetap dapat mengajukan permintaan.'
            : 'Slot tersedia.'),
]);
```

**Key change:** `$pendingConflict` runs `checkConflict` with `approvedOnly: false` which includes pending statuses — exactly what `createBooking()` will run at submission time. If it returns `true`, the form correctly shows a hard-conflict state instead of the misleading amber "pending" state.

**Important:** `$pendingConflict` is wrapped in `DB::transaction()` because `checkConflict` uses `lockForUpdate()`. The existing `$conflict` call is already wrapped — apply the same pattern.

---

## What This Does NOT Change

- **Conflict logic in `BookingService::checkConflict()`** — the matrix is correct.
- **`createBooking()`** — server-side gate remains `approvedOnly: false` (unchanged).
- **`autoRejectConflicting()`** — auto-reject on approve is correct.
- **`typesConflict()`** — compatibility matrix is correct.
- The `$fullBlocks` and `$pendingBlocks` calendar states remain unchanged.
- The schedule page availability check API endpoint behaviour (already handles conflict/pending correctly for the per-type endpoint).

---

## Verification Steps

After implementing:

1. **Calendar display:**
   - Book `room_only + shared` → admin approves → calendar should show **teal "Ruang Tersewa"** for those hours.
   - Click a teal slot → modal banner explains shared occupancy.

2. **Booking type compatibility via schedule page:**
   - From modal, select `computers_only` → prefill schedule → availability check shows **green** → can submit ✓
   - From modal, select `room_only + shared` → availability check shows **green** → can submit ✓
   - From modal, select `full_room` → availability check shows **red conflict** → submit disabled ✓
   - From modal, select `room_only + exclusive` → availability check shows **red conflict** → submit disabled ✓

3. **Stacking shared bookings:**
   - Two approved `room_only + shared` for the same slot → calendar still shows teal (not red hard-block) → new `room_only + shared` can still be submitted ✓

4. **hasPending type-aware:**
   - When `room_only + shared` is pending (not yet approved) and user checks `full_room` → API returns `available: false` (pendingConflict = true) → form shows red conflict → submit disabled ✓
   - When `room_only + shared` is pending and user checks `computers_only` → API returns `available: true, pending: true` → amber warning shown → can submit ✓

---

## Risk Assessment

| Change | Risk | Notes |
|---|---|---|
| `$sharedRoomBlocks` in controller | Low | Additive; new variable only |
| Calendar JS + CSS | Low | New rendering branch; existing branches unchanged |
| `$pendingConflict` in check API | Medium | Changes `available` response from `true` to `false` for incompatible-pending combinations — may affect existing JS clients, verify schedule page handles `conflict` vs `pending` correctly |
| `compact()` update | Low | Additive |

---

## File Change Summary

```
app/Http/Controllers/BookingController.php
  + $sharedRoomBlocks computation after line 124
  + add 'sharedRoomBlocks' to compact() on line 142

resources/views/dashboard.blade.php
  + .cal-slot.slot-shared CSS rule
  + SHARED_ROOM_BLOCKS JS constant
  + sharedRoom state in renderTimeSlots()
  + slot-shared class applied in renderTimeSlots()
  + sharedRoom info banner in openSlotModal()

app/Http/Controllers/Api/AvailabilityController.php
  + $pendingConflict DB::transaction block (replaces simple $hasPending query)
  + updated $hasPending condition (now !$conflict && !$pendingConflict && ...)
  + updated response: 'available' now also false when $pendingConflict
  + updated message for pendingConflict case
```
