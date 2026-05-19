# PLAN: End-Time Boundary / Buffer Asymmetry Fix

## Problem Statement

A booking from **11:00–13:00** ends at 13:00. The dashboard calendar immediately
shows 13:00 as "available" (the slot turns white). However, when a user tries to
book computers starting at **13:00**, the system rejects the request with
*"Slot ini sudah terpesan atau bertabrakan dengan reservasi lain."*

The same behaviour affects the `/api/computers/available` endpoint: the PC grid
on the schedule page still shows all computers as "Terpakai" at 13:00 even
though the blocking booking has technically ended.

---

## Root Cause

### Calendar display — no buffer

`BookingController::dashboard()` computes `$fullBlocks` and `$userEvents` using:

```php
$start = (int) Carbon::parse($b->start_time)->hour;   // 11
$end   = (int) Carbon::parse($b->end_time)->hour;     // 13
range($start, max($start, $end - 1))                  // [11, 12]
```

Hour **13 is NOT in that range**, so the calendar correctly shows 13:00 as free.

### Conflict checker — buffer applied

`BookingService::checkConflict()` and `AvailabilityController::availableComputers()`
both expand the window by `buffer_minutes` (default 15) on **both sides**:

```php
$buffStart = Carbon::parse($startTime)->subMinutes($buffer)->format('H:i:s'); // 12:45
$buffEnd   = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i:s');   // 13:15 (new booking end, not relevant here)
```

Then the overlap query is:

```sql
existing.start_time < buffEnd   -- 11:00 < 13:15  ✓
existing.end_time   > buffStart -- 13:00 > 12:45  ✓  ← FALSE POSITIVE
```

The existing booking's `end_time` (13:00) is greater than the new booking's
`buffStart` (12:45), so the query returns a conflict — even though the two
bookings are perfectly adjacent and share no actual time.

### Summary of the asymmetry

| Layer | Logic | Result at 13:00 |
|---|---|---|
| Calendar (`$fullBlocks`) | `range(start_hour, end_hour - 1)` | **Available** ✓ |
| `checkConflict()` | `existing.end > newStart − 15 min` | **Blocked** ✗ |
| `availableComputers()` | same buffer logic | **Blocked** ✗ |

---

## Fix Options

### Option A — One-sided buffer on the `end_time` comparison *(Recommended)*

Keep the buffer on the `start_time <` side (ensures a gap *before* the existing
booking ends relative to the new booking's end) but **remove the buffer from the
`end_time >` side**.

Change:
```php
->where('end_time', '>', $buffStart)   // current (buffered)
```
To:
```php
->where('end_time', '>', $validated['start_time'])  // new: strict adjacency allowed
```

**Effect:**
- Existing ends at 13:00, new starts at 13:00 → `13:00 > 13:00` is **false** → no conflict ✓
- Existing ends at 13:10, new starts at 13:00 → `13:10 > 13:00` is **true** → conflict ✓
- Existing ends at 12:50, new starts at 13:00 → `12:50 > 13:00` is **false** → no conflict ✓

The buffer on the new booking's *end* side (`start_time < buffEnd`) still
prevents stacking a new booking that *ends* inside the buffer zone of a later
existing booking.

**Tradeoff:** Adjacent bookings (0-minute gap) are allowed. The buffer effectively
becomes one-directional — it only blocks bookings whose end overlaps a future
booking's buffer zone, not bookings that start exactly when another ends.
This matches what users already see in the calendar.

---

### Option B — Remove buffer from both sides *(simple but loses gap enforcement)*

Use raw `start_time` and `end_time` for both comparisons, no buffer at all:

```php
->where('start_time', '<', $validated['end_time'])
->where('end_time',   '>', $validated['start_time'])
```

**Effect:** Only genuine overlaps are blocked. Back-to-back bookings with no gap
are freely allowed.

**Tradeoff:** The 15-minute cleanup/setup buffer intended to be enforced between
bookings is completely removed. Not recommended unless the admin removes the
`buffer_minutes` setting anyway.

---

### Option C — Reflect the buffer in the calendar *(correct but bad UX)*

Apply the 15-minute buffer when computing `$fullBlocks` so the calendar marks
the 13:00 slot as "buffer zone / unavailable".

**Tradeoff:** The calendar becomes confusing — a booking ending at 13:00 would
visually block 13:00 even though the room is technically free. Users would need
to book at 13:15 minimum after a 13:00 release. This is technically honest but
violates user expectations that end-time = release-time.

---

## Chosen Fix: Option A

Apply Option A in **three places**:

1. `BookingService::checkConflict()` — the main conflict gate
2. `AvailabilityController::availableComputers()` — PC grid on schedule page
3. *(No change needed to `BookingController::dashboard()`* — calendar already
   uses the correct no-buffer display)

---

## Files to Change

| File | Line(s) | Change |
|---|---|---|
| `app/Services/BookingService.php` | ~52 | `->where('end_time', '>', $buffStart)` → `->where('end_time', '>', $endTime)` |
| `app/Http/Controllers/Api/AvailabilityController.php` | ~69 | `->where('end_time', '>', $buffStart)` → `->where('end_time', '>', $validated['start_time'])` |

Note: `$buffStart` is still computed and used for the `start_time <` side — only
the `end_time >` clause changes.

---

## Step-by-Step Implementation

### Step 1 — Fix `BookingService::checkConflict()`

In `app/Services/BookingService.php`, the `$base` query builder (around line 48–54):

```php
// BEFORE
$base = Booking::query()
    ->where('date', $date)
    ->whereIn('status', self::ACTIVE_STATUSES)
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $buffStart)   // ← uses buffer
    ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
    ->lockForUpdate();

// AFTER
$base = Booking::query()
    ->where('date', $date)
    ->whereIn('status', self::ACTIVE_STATUSES)
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $startTime)   // ← strict: existing must end AFTER new start
    ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
    ->lockForUpdate();
```

`$startTime` is already a parameter of `checkConflict()`, so no new variable needed.

### Step 2 — Fix `AvailabilityController::availableComputers()`

In `app/Http/Controllers/Api/AvailabilityController.php`, the `$overlapping` query (around line 65–71):

```php
// BEFORE
$overlapping = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $buffStart)   // ← uses buffer
    ->with('computers')
    ->get();

// AFTER
$overlapping = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $validated['start_time'])  // ← strict
    ->with('computers')
    ->get();
```

### Step 3 — Verify the calendar display is already correct

`BookingController::dashboard()` already uses `range($start, $end - 1)` which
does NOT include the end hour. No change needed here.

### Step 4 — PHP lint check

```bash
php -l app/Services/BookingService.php
php -l app/Http/Controllers/Api/AvailabilityController.php
```

### Step 5 — Manual test scenarios

| Scenario | Expected result |
|---|---|
| Booking A: 11:00–13:00. Try booking B: 13:00–15:00 | **Allowed** |
| Booking A: 11:00–13:00. Try booking B: 12:00–14:00 | Blocked (overlap) |
| Booking A: 11:00–13:00. Try booking B: 12:50–14:00 | Blocked (5-min overlap) |
| Booking A: 11:00–13:00. Try booking B: 13:05–15:00 | **Allowed** (5-min gap, buffer only on start_time side) |
| Calendar at 13:00 after A | Shows **Tersedia** |
| PC grid at 13:00 after A | Shows **Tersedia** |

---

## Risk Assessment

**Low risk.** The only behavioural change is that bookings starting exactly at (or
just after) a previous booking's end time are no longer falsely rejected. All
genuine overlaps are still caught by the `start_time < buffEnd` clause and by
the direct `end_time > startTime` comparison. No schema changes. No migration
needed.
