# Plan: Exclusive `room_only` Booking Should Block Computer Reservations

**Date:** 2026-05-18
**Branch:** `CoreBookingBackEnd`
**Status:** Pending Implementation
**Related:** [PLAN-CALENDAR-BOOKING-FIX.md](PLAN-CALENDAR-BOOKING-FIX.md) (the `$fullBlocks` logic added there is one of the surfaces that needs the same fix)

---

## 1. Problem Statement

### Reported bug

1. User A books `room_only` with `room_sharing = exclusive` for 11:00–12:00 → admin approves.
2. User B then opens the booking-schedule page (or the dashboard slot modal) for the same 11:00–12:00 slot.
3. **Expected:** All computer cards show as "Terpakai" (or no computers are pickable), because exclusive use of the room means no one else can be in the room — including using its PCs.
4. **Actual:** Computer cards still appear as "Tersedia". User B can select PCs and submit a booking that — at the very least — semantically conflicts with the exclusive room reservation.

### Why this is wrong

The four booking modes have these semantics:

| Mode | What it claims |
|---|---|
| `full_room` | The whole space + all PCs are mine, no one else in the room. |
| `room_only` + `exclusive` | The whole space is mine; I don't need PCs, but **no one else may be in the room**. |
| `room_only` + `shared` | I need the room, others may also be in the room. |
| `computers_only` | I just need specific PCs; I'll share the room with whoever else is there. |

If an `exclusive` `room_only` booking exists, no other person can physically be in the room — which means no one can sit at a PC, so `computers_only` bookings must also be blocked for that slot.

The current code only treats `full_room` as "blocks everything for computers". `room_only`+`exclusive` was overlooked at three layers (conflict service, API endpoint, calendar pre-computation).

---

## 2. Root Cause Analysis

### 2.1 `BookingService::checkConflict()` — `computers_only` branch

**File:** [app/Services/BookingService.php:60-71](../app/Services/BookingService.php#L60-L71)

```php
if ($bookingType === 'computers_only') {
    if ((clone $base)->where('booking_type', 'full_room')->exists()) {
        return true;          // ← only full_room blocks ALL computers
    }
    if (empty($computerIds)) {
        return false;
    }
    return (clone $base)
        ->where('booking_type', 'computers_only')
        ->whereHas('computers', fn($q) => $q->whereIn('computers.id', $computerIds))
        ->exists();
}
```

There is no check for `room_only` with `room_sharing = 'exclusive'`. A pending `computers_only` request can pass conflict validation even when an exclusive room booking already occupies the slot.

### 2.2 `BookingService::checkConflict()` — `room_only` `exclusive` branch (symmetric inverse)

**File:** [app/Services/BookingService.php:73-87](../app/Services/BookingService.php#L73-L87)

```php
if ($bookingType === 'room_only') {
    if ((clone $base)->where('booking_type', 'full_room')->exists()) {
        return true;
    }
    if ($roomSharing === 'exclusive') {
        return (clone $base)->where('booking_type', 'room_only')->exists();
        // ← does NOT check for existing computers_only bookings
    }
    ...
}
```

The inverse case is also broken: if PC-5 is booked via `computers_only` for 11:00–12:00, a new `room_only` `exclusive` for the same slot would (incorrectly) succeed. The user explicitly reported only the forward direction, but the data model is symmetric — if A blocks B, B should block A — and we should fix both to keep the system internally consistent. Otherwise, the order of bookings determines whether a logical conflict gets caught.

### 2.3 `AvailabilityController::availableComputers()`

**File:** [app/Http/Controllers/Api/AvailabilityController.php:73-83](../app/Http/Controllers/Api/AvailabilityController.php#L73-L83)

```php
$hasFullRoom = $overlapping->where('booking_type', 'full_room')->isNotEmpty();

$bookedIds = $hasFullRoom
    ? Computer::pluck('id')->toArray()
    : $overlapping
        ->where('booking_type', 'computers_only')
        ->flatMap(fn ($b) => $b->computers->pluck('id'))
        ->unique()
        ->values()
        ->toArray();
```

The "ALL computers unavailable" path is only triggered by `full_room`. An exclusive `room_only` booking passes through and produces an empty `$bookedIds` array, so the computer cards in:
- the **dashboard slot modal** (`/api/computers/available` consumer), and
- the **booking schedule page** (after PLAN-CALENDAR-BOOKING-FIX.md added Alpine `loadPcAvailability()`)

…all render as "Tersedia".

### 2.4 `BookingController::dashboard()` — `$fullBlocks` (recently added)

**File:** [app/Http/Controllers/BookingController.php:64-110](../app/Http/Controllers/BookingController.php) (added in the previous fix)

```php
foreach ($allHours as $hour) {
    $hasFullRoom = $hourRanges->contains(
        fn ($r) => $r['booking_type'] === 'full_room' && in_array($hour, $r['hours'], true)
    );
    if ($hasFullRoom) {
        $blockedHours[] = $hour;
        continue;
    }
    $bookedPcIds = $hourRanges
        ->filter(fn ($r) => $r['booking_type'] === 'computers_only' && in_array($hour, $r['hours'], true))
        ->flatMap(fn ($r) => $r['computer_ids'])
        ->unique();
    if ($totalOnline > 0 && $bookedPcIds->count() >= $totalOnline) {
        $blockedHours[] = $hour;
    }
}
```

Exclusive `room_only` bookings are not considered as "fully blocking", so the dashboard calendar will let users click into the slot modal and try to book PCs.

---

## 3. Corrected Conflict Matrix

| Existing → / New ↓ | `full_room` | `computers_only` | `room_only`+excl | `room_only`+shared |
|---|---|---|---|---|
| **`full_room`** | conflict | conflict | conflict | conflict |
| **`computers_only`** | conflict | conflict (if PC overlap) | **conflict** ← fix | OK |
| **`room_only`+exclusive** | conflict | **conflict** ← fix | conflict | conflict |
| **`room_only`+shared** | conflict | OK | conflict | OK |

The two ← fix cells are the gap. Note these are symmetric: `room_only`+exclusive ⇔ `computers_only` must always conflict.

---

## 4. Fix Plan

### Fix A — `BookingService::checkConflict()`

#### A-1: `computers_only` should also conflict on exclusive `room_only`

```php
if ($bookingType === 'computers_only') {
    if ((clone $base)->where('booking_type', 'full_room')->exists()) {
        return true;
    }
    // NEW: exclusive room_only takes the whole space — no computers can be used in it
    if ((clone $base)
            ->where('booking_type', 'room_only')
            ->where('room_sharing', 'exclusive')
            ->exists()) {
        return true;
    }
    if (empty($computerIds)) {
        return false;
    }
    return (clone $base)
        ->where('booking_type', 'computers_only')
        ->whereHas('computers', fn($q) => $q->whereIn('computers.id', $computerIds))
        ->exists();
}
```

#### A-2: `room_only` `exclusive` should also conflict on any existing `computers_only`

```php
if ($bookingType === 'room_only') {
    if ((clone $base)->where('booking_type', 'full_room')->exists()) {
        return true;
    }

    if ($roomSharing === 'exclusive') {
        // Any other room_only OR any computers_only present → cannot claim exclusive room
        if ((clone $base)->where('booking_type', 'room_only')->exists()) {
            return true;
        }
        if ((clone $base)->where('booking_type', 'computers_only')->exists()) {
            return true;
        }
        return false;
    }

    if ($roomSharing === 'shared') {
        return (clone $base)
            ->where('booking_type', 'room_only')
            ->where('room_sharing', 'exclusive')
            ->exists();
    }
}
```

> **Why fix the inverse too:** the underlying invariant is "an exclusive room booking and a computers booking cannot coexist in the same window". Enforcing it on only one side means the outcome depends on submission order — an ordering bug that will eventually surface. Fix both at once.

---

### Fix B — `AvailabilityController::availableComputers()`

Treat `room_only` + `exclusive` the same way as `full_room` for the "all unavailable" path.

```php
$hasFullRoom        = $overlapping->where('booking_type', 'full_room')->isNotEmpty();
$hasExclusiveRoom   = $overlapping
    ->where('booking_type', 'room_only')
    ->where('room_sharing', 'exclusive')
    ->isNotEmpty();

$allBlocked = $hasFullRoom || $hasExclusiveRoom;

$bookedIds = $allBlocked
    ? Computer::pluck('id')->toArray()
    : $overlapping
        ->where('booking_type', 'computers_only')
        ->flatMap(fn ($b) => $b->computers->pluck('id'))
        ->unique()
        ->values()
        ->toArray();
```

After this change:
- The dashboard slot modal will render all 9 PCs as "Terpakai" when an exclusive room booking exists in the slot.
- The booking schedule page's dynamic grid (driven by the same endpoint) will disable every PC.
- The status banner above the grid in the booking page (already from the prior fix) will continue to show "Tersedia / Terpakai" semantics correctly.

> **Optional enhancement:** the JSON response could additionally include a top-level `reason: 'exclusive_room_blocked'` so the UI can show a tailored message ("Slot ini dipesan secara eksklusif sebagai ruang"). This is **out of scope** for this fix — the existing "Terpakai" label communicates unavailability adequately.

---

### Fix C — `BookingController::dashboard()` `$fullBlocks` computation

Extend the existing per-hour loop to treat exclusive `room_only` bookings as a full block.

```php
$hourRanges = $dayBookings->map(function ($b) {
    $start = (int) Carbon::parse($b->start_time)->hour;
    $end   = (int) Carbon::parse($b->end_time)->hour;
    return [
        'hours'        => range($start, max($start, $end - 1)),
        'booking_type' => $b->booking_type,
        'room_sharing' => $b->room_sharing,         // NEW: select this column too
        'computer_ids' => $b->computers->pluck('id')->toArray(),
    ];
});

foreach ($allHours as $hour) {
    // (a) full_room blocks the slot
    $hasFullRoom = $hourRanges->contains(
        fn ($r) => $r['booking_type'] === 'full_room' && in_array($hour, $r['hours'], true)
    );
    // (b) NEW: exclusive room_only also blocks the slot
    $hasExclusiveRoom = $hourRanges->contains(
        fn ($r) => $r['booking_type'] === 'room_only'
                && $r['room_sharing'] === 'exclusive'
                && in_array($hour, $r['hours'], true)
    );
    if ($hasFullRoom || $hasExclusiveRoom) {
        $blockedHours[] = $hour;
        continue;
    }
    // (c) computers_only saturation (unchanged)
    $bookedPcIds = $hourRanges
        ->filter(fn ($r) => $r['booking_type'] === 'computers_only' && in_array($hour, $r['hours'], true))
        ->flatMap(fn ($r) => $r['computer_ids'])
        ->unique();
    if ($totalOnline > 0 && $bookedPcIds->count() >= $totalOnline) {
        $blockedHours[] = $hour;
    }
}
```

Also extend the `$monthBookings` query to select the `room_sharing` column:

```php
$monthBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date',  now()->year)
    ->get(['id', 'date', 'start_time', 'end_time', 'booking_type', 'room_sharing']);   // ← add room_sharing
```

After this fix: a calendar day that has an exclusive `room_only` booking from 11:00–12:00 will render the 11:00 slot as `.slot-booked` (non-clickable "Penuh"), preventing the user from even opening the modal.

---

## 5. Files to Change

| File | Change |
|---|---|
| `app/Services/BookingService.php` | A-1: add exclusive `room_only` check to `computers_only` branch. A-2: add `computers_only` check to `room_only` `exclusive` branch. |
| `app/Http/Controllers/Api/AvailabilityController.php` | B: combine `full_room` and exclusive `room_only` into a single "all blocked" gate. |
| `app/Http/Controllers/BookingController.php` | C: select `room_sharing` column; treat exclusive `room_only` as a full block in `$fullBlocks`. |

No new routes, no migrations. No view changes — the existing UI already reflects "all PCs Terpakai" correctly via the endpoint.

---

## 6. Step-by-Step Implementation Order

```
Step 1  app/Services/BookingService.php — checkConflict()
        - computers_only branch: add the exclusive room_only existence check
          (before the empty($computerIds) short-circuit)
        - room_only / exclusive branch: also check for computers_only

Step 2  app/Http/Controllers/Api/AvailabilityController.php
        - Add $hasExclusiveRoom check
        - Combine with $hasFullRoom into $allBlocked
        - Use $allBlocked for the "ALL computers blocked" path

Step 3  app/Http/Controllers/BookingController.php — dashboard()
        - Add 'room_sharing' to the $monthBookings ->get([...]) column list
        - Add 'room_sharing' to the $hourRanges mapper return shape
        - Add $hasExclusiveRoom branch in the per-hour loop, OR-ed with $hasFullRoom

Step 4  Validation
        - php -l on all three changed files
        - php artisan view:cache (only confirms no regressions in templates;
          views weren't changed but the data shape is wider)
        - Manual smoke test (see §8)
```

---

## 7. Edge Cases & Rules

- **Buffer minutes** (`buffer_minutes` lab setting) already widens the conflict window on both sides in `checkConflict()`; the new exclusive-room check piggybacks on the same `$base` query, so the buffer continues to apply.
- **Cancelled / rejected / completed bookings:** the `$base` query filters to `submitted | under_review | approved`. Exclusive room reservations that were later cancelled correctly drop out of the conflict set — no special handling needed.
- **Past-dated exclusive rooms:** the calendar `$monthBookings` query is unfiltered by date within the month, so a past-but-not-yet-marked-completed exclusive room would still block future-of-day display. Acceptable: if the slot is in the past, the user can't book it anyway (Sunday/past disabling logic).
- **An exclusive room booking that someone is editing back to `shared`:** the booking service uses the booking's current persisted `room_sharing` value, so the moment an admin/owner flips it to `shared`, conflict checks for that slot relax automatically. No cache to invalidate.
- **`computers_only` with empty `computerIds`:** currently returns `false` (no conflict). With Fix A-1, the exclusive `room_only` check runs *before* the empty-array short-circuit, so an empty-computer request will still be correctly rejected if an exclusive room booking exists. This matters because the booking validation requires at least one computer, but the conflict checker is called from API endpoints too where the input may be incomplete.

---

## 8. Testing Checklist

### Conflict service (`BookingService::checkConflict`)
- [ ] Create approved `room_only` `exclusive` for tomorrow 11:00–12:00. Then attempt `computers_only` with PC-1 for 11:00–12:00 → conflict.
- [ ] Same setup, then attempt `computers_only` for 13:00–14:00 (non-overlapping) → no conflict.
- [ ] Create approved `computers_only` (PC-1) for 11:00–12:00. Then attempt `room_only` `exclusive` for 11:00–12:00 → conflict.
- [ ] Create approved `room_only` `shared` for 11:00–12:00. Then attempt `computers_only` for the same slot → no conflict (shared rooms allow computer use).
- [ ] Buffer: with `buffer_minutes=15`, exclusive room 11:00–12:00 should block `computers_only` 12:00–13:00 (because 12:00 ≤ 12:15 buffer).

### Availability endpoint (`/api/computers/available`)
- [ ] No bookings → all 9 PCs `available: true` (except offline/maintenance).
- [ ] Approved `room_only` `exclusive` for the slot → all 9 PCs `available: false`.
- [ ] Approved `room_only` `shared` for the slot → all 9 online PCs `available: true`.
- [ ] Approved `full_room` (existing behavior) → all 9 PCs `available: false` (unchanged).
- [ ] Mixed: `computers_only` PC-5 + `room_only` `shared` for the slot → PC-5 unavailable, others available.

### Dashboard calendar (`$fullBlocks`)
- [ ] Approved `room_only` `exclusive` for day D, 11:00–12:00 → slot card renders as `.slot-booked` ("Penuh", non-clickable).
- [ ] `room_only` `shared` for the same → slot remains clickable.
- [ ] Mix `room_only` exclusive (11:00–12:00) and `computers_only` PC-1 (13:00–14:00) → 11:00 = Penuh, 13:00 = clickable (since only 1/9 PCs taken).

### Booking schedule page
- [ ] Pick date/time covered by an exclusive `room_only` → grid renders all PCs as "Terpakai", none selectable.
- [ ] Change time outside that window → grid refreshes, PCs become "Tersedia".

---

## 9. Out of Scope

- A user-facing message explaining *why* the slot is unbookable ("ruang dipesan eksklusif"). The current "Terpakai" / "Penuh" labels are sufficient. A future UX pass could add `reason` codes to the API for richer messaging.
- Admin UI to flag/override an exclusive room booking. Approval workflow already gates these.
- Backfilling old conflicting bookings that may have been created before this fix. A separate audit script could find any `computers_only` overlapping an exclusive `room_only` and flag them for admin review — recommended as a one-off follow-up if any inconsistent rows exist in production.
