# PLAN: Booking Type Restriction — Schedule Page & "Saya" Slot

**Branch:** `CoreBookingBackEnd`  
**Date:** 2026-05-20  
**Status:** DRAFT — awaiting review

---

## Problem Statement

Two edge cases remain after the Room-Sharing Conflict fix:

**Edge Case 1:** When a user navigates from the dashboard calendar modal (which correctly disables room-type options for a shared-room slot) to the schedule/request page, the schedule page has no awareness of the shared-room restriction. All three booking-type radio buttons (Komputer, Ruang + Komputer, Ruang Saja) remain fully enabled. The user can click any of them, run the availability check (which correctly shows conflict), see a red warning — but the flow is confusing and the types are not proactively disabled.

**Edge Case 2:** When user 1 has a `room_only + shared` booking ("Saya" slot on the calendar), clicking that slot opens the booking modal without any shared-room restriction. User 1 can select `full_room` or `room_only` and navigate to the schedule page, potentially submitting a second conflicting booking for the same time slot.

---

## Root-Cause Analysis

### Edge Case 1 — Schedule page has no shared-room context

**File:** `resources/views/dashboard.blade.php` — `navigateToBooking()` (line ~727)

```js
function navigateToBooking() {
    ...
    params.set('type', typeMap[currentResType] || currentResType);
    // ← no shared-room flag passed in URL params
    window.location.href = BOOKING_SCHEDULE_URL + '?' + params.toString();
}
```

**File:** `app/Http/Controllers/BookingController.php` — `showSchedule()` (line ~170)

```php
$prefill = [
    'type'         => $typeMap[$rawType] ?? $rawType,
    'date'         => ...,
    'start_time'   => ...,
    'end_time'     => ...,
    'room_sharing' => ...,
    'computers'    => ...,
    // ← no 'shared_room_active' key stored in draft
];
```

**File:** `resources/views/booking/schedule.blade.php`

The three type radio inputs are always rendered as enabled with no conditional disabling from the draft.

**Result:** The modal enforces type restrictions, but once the user lands on the schedule page, all type options are re-enabled. Switching to `full_room` triggers an availability conflict (correctly), but the option should never have been clickable in the first place.

---

### Edge Case 2 — `sharedRoom` flag excluded for "Saya" slots

**File:** `resources/views/dashboard.blade.php` — `renderTimeSlots()` (line ~563)

```js
const sharedRoom = !hardBlocked && !isMine && !softPending   // ← !isMine excludes own slots
                   && SHARED_ROOM_BLOCKS[day] && SHARED_ROOM_BLOCKS[day].includes(hourKey);
...
if (!hardBlocked) el.addEventListener('click', () => openSlotModal(day, slot, { softPending, isMine, sharedRoom }));
```

When user 1 has a `room_only + shared` booking:
- `isMine = true` (their booking is in `USER_EVENTS`)
- `SHARED_ROOM_BLOCKS[day]` contains that hour (their booking is `room_only + shared`)
- But `!isMine = false` → `sharedRoom = false`
- `openSlotModal` receives `sharedRoom: false` → no type restrictions applied

**Result:** User 1 can click their own "Saya" slot, see all three type options enabled, navigate to the schedule page, and submit a new booking for the same slot. The `createBooking()` server-side check would catch the most severe conflicts (`full_room` fails because of their own approved booking), but `computers_only` would succeed — creating a genuine double-booking for the same user on the same slot.

---

## Files to Change

| File | Change |
|---|---|
| `resources/views/dashboard.blade.php` | (1) Pass `room_shared=1` in `navigateToBooking()` URL params; (2) Fix `sharedRoom` computation in `renderTimeSlots()` |
| `app/Http/Controllers/BookingController.php` | Store `shared_room_active` in session draft when `room_shared` URL param is present |
| `resources/views/booking/schedule.blade.php` | Disable incompatible type radio buttons when `$draft['shared_room_active']` is true |

---

## Fix 1 — Pass `room_shared` flag from Modal to Schedule Page

### 1a. `dashboard.blade.php` — `navigateToBooking()`

Track the `sharedRoom` state in a module-level variable so `navigateToBooking()` can access it (it currently only reads `currentResType`, `currentSharing`, `selectedPcIds`, `modalDay`, `modalSlot`).

**Add** a module-level variable near the top of the script block:

```js
let currentSlotIsSharedRoom = false;
```

**Update** `openSlotModal()` to set it:

```js
// Near the start of openSlotModal(), after reading opts:
currentSlotIsSharedRoom = sharedRoom;
```

**Update** `navigateToBooking()` to include the flag in URL params:

```js
function navigateToBooking() {
    ...
    if (currentSlotIsSharedRoom) {
        params.set('room_shared', '1');
    }
    window.location.href = BOOKING_SCHEDULE_URL + '?' + params.toString();
}
```

### 1b. `BookingController::showSchedule()` — store flag in session draft

```php
$prefill = [
    'type'               => $typeMap[$rawType] ?? $rawType,
    'date'               => $request->input('date', ''),
    'start_time'         => $request->input('start_time', ''),
    'end_time'           => $request->input('end_time', ''),
    'room_sharing'       => $request->input('room_sharing'),
    'computers'          => array_map('intval', (array) $request->input('computers', [])),
    'shared_room_active' => $request->boolean('room_shared'),  // ← add this line
];
```

### 1c. `schedule.blade.php` — disable incompatible type radio inputs

In the Blade template, each booking-type `<label>` wraps a hidden `<input type="radio">`. When `$draft['shared_room_active']` is true, the `full_room` and `room_only` inputs should be disabled and their wrapper labels should be visually greyed out.

**For `full_room` label** (currently around line 87):

```blade
<label class="block {{ ($draft['shared_room_active'] ?? false) ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'cursor-pointer' }}">
    <input type="radio" name="type" value="full_room" class="sr-only peer" x-model="selected"
           {{ ($draft['shared_room_active'] ?? false) ? 'disabled' : '' }}>
```

**For `room_only` label** (currently around line 113):

```blade
<label class="block {{ ($draft['shared_room_active'] ?? false) ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'cursor-pointer' }}">
    <input type="radio" name="type" value="room_only" class="sr-only peer" x-model="selected"
           {{ ($draft['shared_room_active'] ?? false) ? 'disabled' : '' }}>
```

**Add a contextual hint** below the type selection section (or at the top of the form) when `shared_room_active`:

```blade
@if ($draft['shared_room_active'] ?? false)
<div class="mb-6 flex items-start gap-3 p-4 rounded-lg bg-teal-50 border border-teal-200 text-sm text-teal-800">
    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke-width="2"/>
        <line x1="12" y1="16" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
        <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2" stroke-linecap="round"/>
    </svg>
    Ruangan sudah digunakan berbagi pada slot ini — hanya <strong>Komputer Saja</strong> yang tersedia.
</div>
@endif
```

**Alpine.js init guard:** The `$watch` on `selected` triggers `scheduleAvailabilityCheck()`. If `shared_room_active` is true and the user somehow selects an incompatible type (e.g., via browser devtools), the availability check will show a red conflict and disable submit — the server-side gate remains intact.

---

## Fix 2 — Decouple `sharedRoom` Modal Flag from `isMine` Visual State

### `dashboard.blade.php` — `renderTimeSlots()`

Split the single `sharedRoom` variable into two:
- `sharedRoomModal` — passed to the modal; NOT gated by `!isMine`
- `sharedRoom` (visual) — still gated by `!isMine` so the CSS class `slot-shared` is only applied when the user doesn't own the booking

```js
const hardBlocked     = FULL_BLOCKS[day]    && FULL_BLOCKS[day].includes(hourKey);
const isMine          = USER_EVENTS[day]    && USER_EVENTS[day].includes(hourKey);
const softPending     = !isMine && PENDING_BLOCKS[day] && PENDING_BLOCKS[day].includes(hourKey);

// sharedRoomModal: any active room_only+shared booking exists for this hour, regardless of
// ownership. Passed to the modal so type restrictions are applied even when the slot is "Saya".
const sharedRoomModal = !hardBlocked
                        && SHARED_ROOM_BLOCKS[day]
                        && SHARED_ROOM_BLOCKS[day].includes(hourKey);

// sharedRoom (visual): same as above but only for OTHER users' bookings. Used for CSS class only.
const sharedRoom      = sharedRoomModal && !isMine && !softPending;
```

The class assignment and label remain unchanged (use `sharedRoom` not `sharedRoomModal`):

```js
el.className = 'cal-slot'
    + (hardBlocked                  ? ' slot-booked'  : '')
    + (!hardBlocked && isMine       ? ' slot-mine'    : '')
    + (!hardBlocked && softPending  ? ' slot-pending' : '')
    + (sharedRoom                   ? ' slot-shared'  : '');
...
const statusLabel = hardBlocked ? 'Penuh'
    : isMine        ? 'Saya'
    : softPending   ? 'Menunggu'
    : sharedRoom    ? 'Berbagi'
    : 'Tersedia';
```

The click listener passes `sharedRoomModal` (not `sharedRoom`):

```js
if (!hardBlocked) el.addEventListener('click', () => openSlotModal(day, slot, { softPending, isMine, sharedRoom: sharedRoomModal }));
```

**Effect:**
- User 1's slot still shows as blue "Saya" visually ← unchanged
- When user 1 clicks it, the modal receives `sharedRoom: true` → "Ruang + Komputer" and "Ruang Saja" are disabled → only "Komputer" is enabled
- For a slot where ANOTHER user has `room_only + shared` (not user 1's own), the slot shows as teal "Berbagi" ← unchanged

---

## What This Does NOT Change

- **Server-side conflict checks** — `checkConflict()` and `createBooking()` remain as the hard gate.
- **`autoRejectConflicting()`** — unchanged.
- **The availability check on the schedule page** — still runs automatically; submit remains disabled when conflict is detected (last line of defence).
- **Admin approval flow** — unchanged.

---

## Verification Steps

### Edge Case 1:
1. Book `room_only + shared` → admin approves.
2. Log in as user 2. Click the teal "Berbagi" slot on the calendar.
3. Modal shows only "Komputer" enabled. Click "Lanjut →".
4. Schedule page loads — "Ruang + Komputer" and "Ruang Saja" radio buttons are visually greyed out and non-clickable. Teal info banner is shown.
5. User can only submit with "Komputer Saja". ✓

### Edge Case 2:
1. Book `room_only + shared` → admin approves.
2. Calendar shows the slot as "Saya" (blue) for user 1.
3. User 1 clicks the "Saya" slot.
4. Modal opens — "Ruang + Komputer" and "Ruang Saja" are disabled. Teal banner shown. Only "Komputer" enabled.
5. User 1 can navigate to schedule page with only "Komputer Saja" available. ✓

---

## File Change Summary

```
resources/views/dashboard.blade.php
  + module-level `currentSlotIsSharedRoom` variable
  + set `currentSlotIsSharedRoom = sharedRoom` in openSlotModal()
  + pass `room_shared=1` in navigateToBooking() URL params when sharedRoom=true
  ~ rename/split sharedRoom → sharedRoomModal + sharedRoom in renderTimeSlots()
  ~ pass sharedRoomModal to openSlotModal() click listener

app/Http/Controllers/BookingController.php
  + 'shared_room_active' => $request->boolean('room_shared') in $prefill array

resources/views/booking/schedule.blade.php
  + teal info banner when $draft['shared_room_active']
  ~ full_room label: add disabled + opacity-40 classes when shared_room_active
  ~ room_only label: add disabled + opacity-40 classes when shared_room_active
```

**No new files. No model/migration changes. No changes to BookingService or conflict logic.**
