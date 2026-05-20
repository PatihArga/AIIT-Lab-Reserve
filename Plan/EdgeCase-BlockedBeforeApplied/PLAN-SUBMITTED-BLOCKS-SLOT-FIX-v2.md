# PLAN v2: Submitted Bookings Should Not Hard-Block Slots Before Admin Approval

> Revised after review `EdgeCase-BlockedBeforeApplied/two_plans_review.md`.
> Three critical issues, two design concerns, and five improvements from the
> review have been incorporated. v1 of this plan should be considered superseded.

---

## Problem (unchanged)

`BookingService::ACTIVE_STATUSES = ['submitted', 'under_review', 'approved']`
is used in every availability/conflict layer. As soon as a user submits a
request the slot is hard-blocked lab-wide — before admin approves anything.
Admin can never see or choose between competing requests for the same slot.

---

## Design Decisions (post-review)

| Decision | Rationale |
|---|---|
| Add `approvedOnly` param to `checkConflict()` instead of changing its default | `createBooking()` must still prevent duplicate incompatible submissions (e.g., two `full_room` for the same slot); APIs that only show availability use `approvedOnly: true` |
| Auto-reject in `approve()` uses dedicated `typesConflict()` instead of calling `checkConflict()` per-loop | Avoids running `lockForUpdate` N times in a loop; prevents potential deadlocks |
| Auto-reject lives **inside** the same `DB::transaction` as the approve | Atomicity — partial failure (approve succeeds, auto-reject fails) cannot leave data inconsistent |
| `availableComputers()` and `dashboard()` use **one query, partitioned in PHP** | Halves DB queries vs. two separate queries |
| Buffer applied only to approved hard-block, not to pending soft-block display | Buffer is a scheduling safety margin; pending bookings should display at their actual times |
| User's own pending booking shows "Saya" (blue), not "Menunggu" (yellow) | Consistent with existing `USER_EVENTS` behaviour; avoids confusing the submitter |

---

## Status Semantics After Fix

| Status | Blocks slot for *other* users? | Calendar / PC grid appearance |
|---|---|---|
| `submitted` | No (soft) | Yellow "Menunggu" (clickable) |
| `under_review` | No (soft) | Yellow "Menunggu" (clickable) |
| `approved` | **Yes** (hard) | Dark "Terpesan" (non-clickable) |
| `rejected` / `cancelled` / `completed` | No | Not shown |
| *Own* pending booking | — | Blue "Saya" (existing behaviour) |

---

## Files Changed

| File | What changes |
|---|---|
| `app/Services/BookingService.php` | Add `$approvedOnly` param; extract `autoRejectConflicting()` + `typesConflict()` |
| `app/Http/Controllers/Api/AvailabilityController.php` | `check()` uses `approvedOnly: true`; `availableComputers()` uses single query + partition + `pending` field in response |
| `app/Http/Controllers/BookingController.php` | `dashboard()` uses single query + partition → `$fullBlocks` + `$pendingBlocks` |
| `app/Http/Controllers/Admin/AdminRequestController.php` | `show()` uses `approvedOnly: true`; `approve()` calls `autoRejectConflicting()` inside transaction |
| `resources/views/dashboard.blade.php` | 4-state calendar (free / mine / pending / booked); `$pendingBlocks` JS var; CSS for `.slot-pending` |
| `resources/views/booking/schedule.blade.php` | `availStatus` gets `pending` state; PC grid shows "Menunggu" badge |

---

## Step-by-Step Implementation

---

### Step 1 — `BookingService.php`: add `$approvedOnly`, extract helpers

#### 1-A: Add `$approvedOnly` parameter to `checkConflict()`

```php
public function checkConflict(
    string $date,
    string $startTime,
    string $endTime,
    string $bookingType,
    array  $computerIds = [],
    ?string $roomSharing = null,
    ?int   $excludeBookingId = null,
    bool   $approvedOnly = false,           // NEW
): bool {
    $statuses = $approvedOnly
        ? ['approved']
        : self::ACTIVE_STATUSES;            // ['submitted','under_review','approved']

    $buffer  = (int) LabSetting::get('buffer_minutes', 15);
    $buffEnd = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i:s');

    $base = Booking::query()
        ->where('date', $date)
        ->whereIn('status', $statuses)      // ← uses $statuses, not hardcoded constant
        ->where('start_time', '<', $buffEnd)
        ->where('end_time',   '>', $startTime)
        ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
        ->lockForUpdate();

    // ... rest of method unchanged ...
}
```

`createBooking()` does NOT pass `approvedOnly` → defaults to `false` → still
prevents two users submitting incompatible types (e.g., two `full_room`) for
the same slot simultaneously.

#### 1-B: Add `autoRejectConflicting()` private method

```php
private function autoRejectConflicting(Booking $approved): void
{
    $conflicting = Booking::where('id', '!=', $approved->id)
        ->whereIn('status', ['submitted', 'under_review'])
        ->where('date', $approved->date->format('Y-m-d'))
        ->where('start_time', '<', (string) $approved->end_time)
        ->where('end_time',   '>', (string) $approved->start_time)
        ->with('computers:id')
        ->get();

    foreach ($conflicting as $pending) {
        if (! $this->typesConflict($approved, $pending)) {
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

        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => 'booking.auto_rejected',
            'auditable_type' => Booking::class,
            'auditable_id'   => $pending->id,
            'old_values'     => ['status' => $pending->getOriginal('status')],
            'new_values'     => ['status' => 'rejected'],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
```

#### 1-C: Add `typesConflict()` private method

```php
private function typesConflict(Booking $a, Booking $b): bool
{
    // full_room conflicts with everything
    if ($a->booking_type === 'full_room') return true;
    if ($b->booking_type === 'full_room') return true;

    // exclusive room_only conflicts with everything
    if ($a->booking_type === 'room_only' && $a->room_sharing === 'exclusive') return true;
    if ($b->booking_type === 'room_only' && $b->room_sharing === 'exclusive') return true;

    // computers_only vs computers_only: conflict only if PCs overlap
    if ($a->booking_type === 'computers_only' && $b->booking_type === 'computers_only') {
        return $a->computers->pluck('id')
            ->intersect($b->computers->pluck('id'))
            ->isNotEmpty();
    }

    // room_only shared + room_only shared: compatible
    // computers_only + room_only shared: compatible
    return false;
}
```

Note: `AuditLog` and `auth()` are already imported/used in `BookingService`'s
dependent controller. `autoRejectConflicting()` is called from
`AdminRequestController` which is inside an auth-guarded context, so
`auth()->id()` is safe. If `BookingService` cannot import `AuditLog` directly,
move `autoRejectConflicting()` into `AdminRequestController` as a private
method — the logic is the same either way.

---

### Step 2 — `AvailabilityController.php`

#### `check()` — use `approvedOnly: true`

```php
$conflict = DB::transaction(fn () => $this->bookings->checkConflict(
    date:         $validated['date'],
    startTime:    $validated['start_time'],
    endTime:      $validated['end_time'],
    bookingType:  $validated['type'],
    computerIds:  $validated['computers'] ?? [],
    roomSharing:  $validated['room_sharing'] ?? null,
    approvedOnly: true,                             // NEW
));

// Soft-pending check (no buffer — display at actual times)
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

#### `availableComputers()` — single query, PHP partition

```php
$buffer  = (int) LabSetting::get('buffer_minutes', 15);
$buffEnd = Carbon::parse($validated['end_time'])->addMinutes($buffer)->format('H:i:s');

// Single query — all statuses; partition in PHP (avoids double DB round-trip)
$all = Booking::query()
    ->where('date', $validated['date'])
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->where('start_time', '<', $buffEnd)
    ->where('end_time',   '>', $validated['start_time'])
    ->with('computers')
    ->get();

$approved = $all->where('status', 'approved');
$pending  = $all->whereIn('status', ['submitted', 'under_review']);

// Hard-blocked (approved only)
$hasFullRoom      = $approved->where('booking_type', 'full_room')->isNotEmpty();
$hasExclusiveRoom = $approved->where('booking_type', 'room_only')
                             ->where('room_sharing', 'exclusive')->isNotEmpty();
$allHardBlocked   = $hasFullRoom || $hasExclusiveRoom;

$bookedIds = $allHardBlocked
    ? Computer::pluck('id')->toArray()
    : $approved->where('booking_type', 'computers_only')
        ->flatMap(fn ($b) => $b->computers->pluck('id'))
        ->unique()->values()->toArray();

// Soft-pending (no buffer — display at actual times, already filtered by start_time < end_time)
$hasPendingFullBlock = $pending->whereIn('booking_type', ['full_room'])
                               ->isNotEmpty()
                    || $pending->where('booking_type', 'room_only')
                               ->where('room_sharing', 'exclusive')->isNotEmpty();

$pendingIds = $hasPendingFullBlock
    ? Computer::pluck('id')->toArray()
    : $pending->where('booking_type', 'computers_only')
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

return response()->json(['computers' => $computers]);
```

---

### Step 3 — `BookingController.php` `dashboard()`: single query + partition

Replace the existing `$monthBookings` query and `$fullBlocks` computation with:

```php
// Single query for the month — all active statuses; partition in PHP
$monthBookings = Booking::with('computers:id')
    ->whereIn('status', ['submitted', 'under_review', 'approved'])
    ->whereMonth('date', now()->month)
    ->whereYear('date',  now()->year)
    ->get(['id', 'date', 'start_time', 'end_time', 'booking_type', 'room_sharing', 'status']);

$approvedMonth = $monthBookings->where('status', 'approved');
$pendingMonth  = $monthBookings->whereIn('status', ['submitted', 'under_review']);
```

Run the existing hour-range grouping logic **twice** — once on `$approvedMonth`
to produce `$fullBlocks`, and once on `$pendingMonth` to produce `$pendingBlocks`:

```php
// Helper closure reused for both
$computeHourBlocks = function ($bookings) use ($totalOnline) {
    return $bookings
        ->groupBy(fn ($b) => (int) $b->date->day)
        ->map(function ($dayBookings) use ($totalOnline) {
            // ... existing per-hour loop logic (hasFullRoom, hasExclusiveRoom, PC saturation) ...
            return collect($blockedHours)->unique()->sort()->values();
        });
};

$fullBlocks    = $computeHourBlocks($approvedMonth);   // hard-block
$pendingBlocks = $computeHourBlocks($pendingMonth);    // soft-pending

return view('dashboard', compact(
    'upcomingBookings', 'completedBookings', 'stats',
    'fullBlocks', 'pendingBlocks', 'userEvents'        // ← add $pendingBlocks
));
```

---

### Step 4 — `AdminRequestController.php`

#### `show()` — use `approvedOnly: true`

```php
$hasConflict = DB::transaction(fn () => $this->bookings->checkConflict(
    date:             $booking->date->format('Y-m-d'),
    startTime:        substr((string) $booking->start_time, 0, 5),
    endTime:          substr((string) $booking->end_time, 0, 5),
    bookingType:      $booking->booking_type,
    computerIds:      $booking->computers->pluck('id')->toArray(),
    roomSharing:      $booking->room_sharing,
    excludeBookingId: $booking->id,
    approvedOnly:     true,                             // NEW
));
```

This makes the conflict warning meaningful: it only fires when an *approved*
booking blocks this one, not when two pending bookings coincidentally overlap.

#### `approve()` — call `autoRejectConflicting()` inside the existing transaction

```php
DB::transaction(function () use ($booking, $oldStatus) {
    $conflict = $this->bookings->checkConflict(
        // ... existing args ...
        excludeBookingId: $booking->id,
        // approvedOnly defaults to false → correct, guards against concurrent approvals
    );

    if ($conflict) {
        throw new BookingConflictException(
            'Slot ini sekarang bentrok dengan reservasi lain. Persetujuan dibatalkan.'
        );
    }

    $booking->update([
        'status'      => 'approved',
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
        'admin_notes' => null,
    ]);

    AuditLog::create([...]);  // existing audit log

    // Auto-reject pending bookings that now conflict with the approved one.
    // MUST be inside this transaction so approve + auto-reject are atomic.
    $this->bookings->autoRejectConflicting($booking);
});
```

Note: `autoRejectConflicting()` must be `public` (not `private`) on
`BookingService` so the controller can call it, OR move the method to a private
helper on the controller. Either approach is acceptable.

---

### Step 5 — `dashboard.blade.php`: 4-state calendar

#### JS variables

```js
const FULL_BLOCKS    = @json($fullBlocks);     // approved hard-block
const PENDING_BLOCKS = @json($pendingBlocks);  // submitted/under_review soft
const USER_EVENTS    = @json($userEvents);     // current user's own bookings
```

#### `renderTimeSlots()` — four states with correct priority

```js
const hardBlocked = FULL_BLOCKS[day]    && FULL_BLOCKS[day].includes(hourKey);
const isMine      = USER_EVENTS[day]    && USER_EVENTS[day].includes(hourKey);
// Own pending booking = "Saya", not "Menunggu" — check isMine BEFORE softPending
const softPending = !isMine
                 && PENDING_BLOCKS[day] && PENDING_BLOCKS[day].includes(hourKey);

el.className = 'cal-slot'
    + (hardBlocked             ? ' slot-booked'   : '')
    + (!hardBlocked && isMine  ? ' slot-mine'     : '')
    + (!hardBlocked && !isMine && softPending ? ' slot-pending' : '');

const statusLabel = hardBlocked  ? 'Penuh'
    : isMine                     ? 'Saya'
    : softPending                ? 'Menunggu'
    : 'Tersedia';

// Hard-blocked: no click. Soft-pending and free: allow click (modal may show warning)
if (!hardBlocked) el.addEventListener('click', () => openSlotModal(day, slot));
```

#### CSS — add alongside existing `.slot-booked` and `.slot-mine`

```css
.cal-slot.slot-pending {
    background: #FFFBEB;
    border-color: #FDE68A;
    color: #D97706;
}
```

#### Slot modal — show soft warning when pending

In the `openSlotModal()` function (or the modal template), read whether the
slot has a pending state and display a yellow info banner:
*"Ada permintaan yang sedang dalam peninjauan untuk slot ini. Anda masih dapat mengajukan permintaan."*

---

### Step 6 — `booking/schedule.blade.php`: pending state for availability banner and PC grid

#### Availability status — handle `pending` from API

In the `runAvailabilityCheck()` Alpine function:

```js
// BEFORE
this.availStatus = data.available ? 'available' : 'conflict';

// AFTER
this.availStatus = ! data.available
    ? 'conflict'
    : (data.pending ? 'pending' : 'available');
```

Add a matching display case in the availability banner template:

```html
<template x-if="availStatus === 'pending'">
    <div class="... bg-yellow-50 border-yellow-300 text-yellow-800 ...">
        <!-- warning icon -->
        Ada permintaan yang sedang ditinjau untuk slot ini.
        Anda tetap dapat mengajukan permintaan reservasi.
    </div>
</template>
```

#### PC grid — add `pending` badge

In `getPcState()`:

```js
getPcState(pcId) {
    if (!this.pcAvailability[pcId]) return 'unknown';
    const pc = this.pcAvailability[pcId];
    if (!pc.available) return 'booked';
    if (pc.pending)    return 'pending';
    return 'available';
}
```

Badge display per state:

| State | Badge text | Colour |
|---|---|---|
| `available` | Tersedia | Green |
| `pending` | Menunggu | Yellow/amber |
| `booked` | Terpakai | Grey/red |
| `unknown` / offline | Perawatan | Grey |

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

| Scenario | Expected |
|---|---|
| User A submits `full_room` 11–13. User B views same slot on calendar. | Yellow "Menunggu", clickable |
| User A views their own pending booking on calendar. | Blue "Saya" (not yellow) |
| User A submits. User B submits same slot (different type compatible). | Both in admin queue — no error |
| User A submits `full_room`. User B tries to submit `full_room` same slot. | **Blocked at submission** (createBooking still uses ACTIVE_STATUSES) |
| Admin approves User A. | User B's request auto-rejected inside same transaction |
| Admin approves User A. Check `hasConflict` on another pending in same slot. | Only fires if an *approved* booking conflicts |
| PC grid for 11–13 slot when User A pending `computers_only` with PC-1. | PC-1 shows "Menunggu" (yellow), all others "Tersedia" |
| PC grid for 11–13 slot when User A's request **approved** with PC-1. | PC-1 shows "Terpakai" (hard) |
| Availability banner for slot with only pending requests. | Yellow warning — "Anda tetap dapat mengajukan" |
| Availability banner for slot with approved booking. | Red conflict — cannot book |

---

## Risk Assessment

**Medium risk — behaviour change is intentional.**

- Two users can now submit the same slot if types are compatible. This is
  expected and handled by the auto-reject on approve.
- `full_room` and exclusive `room_only` still can't be double-submitted
  because `createBooking()` still calls `checkConflict()` with
  `approvedOnly: false`.
- Auto-reject inside the transaction guarantees atomicity.
- No schema changes. No migration needed.
