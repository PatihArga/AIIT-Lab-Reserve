# REVIEW: Booking Type Restriction — Schedule Page & "Saya" Slot

**Reviewer:** AI Assistant  
**Date:** 2026-05-20  
**Plan reviewed:** `PLAN-BOOKING-TYPE-RESTRICTION-FIX.md`  
**Status:** APPROVED with refinements

---

## Files Verified Against Plan

| File | Lines Read | Plan References Accurate? |
|------|-----------|--------------------------|
| `resources/views/dashboard.blade.php` | 1–824 (full) | ✅ Yes |
| `app/Http/Controllers/BookingController.php` | 150–188 (showSchedule) | ✅ Yes |
| `resources/views/booking/schedule.blade.php` | 1–603 (full) | ✅ Yes |

---

## Verdict

**The plan is solid and ready to implement**, with refinements recommended below. Both edge cases are correctly identified and the proposed fixes are surgically scoped.

---

## What's Correct

### Edge Case 1 — Root Cause Confirmed

Verified in `dashboard.blade.php` line 772–793. The `navigateToBooking()` function builds a URL with `type`, `date`, `start_time`, `end_time`, `room_sharing`, and `computers[]` — but **no `room_shared` flag**.

Verified in `BookingController.php` lines 173–180. The `$prefill` array stores the above fields into `booking_draft.schedule` session — but has **no `shared_room_active` key**.

Verified in `schedule.blade.php` lines 61–152. All three booking-type radio inputs (`computers_only`, `full_room`, `room_only`) are always rendered enabled with `cursor-pointer` — there is **no conditional disabling** from the draft.

**Result:** The modal correctly disables `both` and `room` type cards (line 644: `if (sharedRoom && (t === 'both' || t === 'room')) c.classList.add('is-disabled')`), but once the user lands on the schedule page, this restriction is lost. The availability API (`runAvailabilityCheck()`) at line 497–528 will correctly detect the conflict and show red, but the user can still see and click incompatible types — confusing UX.

### Edge Case 2 — Root Cause Confirmed

Verified in `dashboard.blade.php` lines 560–564:

```js
const sharedRoom = !hardBlocked && !isMine && !softPending
                   && SHARED_ROOM_BLOCKS[day] && SHARED_ROOM_BLOCKS[day].includes(hourKey);
```

The `!isMine` guard means: when user 1 has a `room_only + shared` booking, `isMine = true` → `sharedRoom = false` → the modal opens without any type restrictions.

Verified at line 584: `openSlotModal(day, slot, { softPending, isMine, sharedRoom })` — passes `sharedRoom: false` for user 1's own slot.

**Result:** User 1 clicking their "Saya" slot gets all three types enabled. They could navigate to schedule page with `full_room` selected and attempt to submit — `createBooking()` would catch it server-side, but the UX path should never have allowed it.

### Fix 1 — `room_shared` Flag Propagation

The approach is correct: pass `room_shared=1` as a URL param → store `shared_room_active` in session → use it in Blade to disable `full_room` and `room_only` inputs.

The plan correctly identifies the three touch points:
1. `navigateToBooking()` — add `room_shared=1` param ✅
2. `BookingController::showSchedule()` — store `shared_room_active` in `$prefill` ✅
3. `schedule.blade.php` — disable radio inputs + show banner ✅

### Fix 2 — Decouple `sharedRoomModal` from Visual `sharedRoom`

The split into two variables is the right approach:
- `sharedRoomModal` — ownership-agnostic, passed to modal for type restriction
- `sharedRoom` — ownership-gated, used for CSS visual state only

This ensures user 1's own "Saya" slot still appears blue visually, but the modal correctly applies type restrictions.

---

## Recommended Refinements

### ~~Refinement 1~~ — WITHDRAWN: Disabling all room options is correct

> [!NOTE]
> **Original suggestion:** Allow `room_only + shared` to remain enabled on the schedule page since the conflict matrix marks it as compatible.
>
> **User's correction:** Even though `room_only + shared` is technically compatible, leaving it enabled would let multiple users spam `room_only + shared` requests for the same slot, creating an unmanageable pile-up for the admin. The room is a finite physical space — unlimited "shared" requests degrade the booking system's usefulness.

**The plan's approach is correct:** when `shared_room_active` is true, **only `computers_only` should be available** on both the dashboard modal and the schedule page. This is a deliberate business constraint, not a technical oversight.

- `full_room` → **disabled** ✅
- `room_only` (both exclusive AND shared) → **disabled** ✅
- `computers_only` → **enabled** ✅

No change needed — the plan stands as written.

---

### Refinement 2: Edge case within Edge Case 2 — User 1 booking `computers_only` on their own shared-room slot

The plan states:

> User 1 clicking their "Saya" slot gets all three types enabled. They could navigate to schedule page with `full_room` selected and attempt to submit — `createBooking()` would catch it server-side, but the UX path should never have allowed it.

But actually, `computers_only` on the same slot IS valid. And the plan's Fix 2 will make `sharedRoomModal = true` for user 1's slot, which means only `computer` type will be available in the modal. This is **correct** — user 1 can add a `computers_only` booking on their same shared-room time, which doesn't conflict.

However, there's a subtle issue: **what if user 1's own booking is the ONLY shared-room booking on this slot, and they want to add a second `room_only + shared` booking?** This is a valid (if unusual) scenario — two shared-room bookings by the same user. With the current fix, this would be blocked in the modal (only `computer` enabled). But the availability API and `createBooking()` would actually allow it.

**Assessment:** This is an extremely unlikely scenario (same user making two shared-room bookings for the same time). The current behavior (blocking it in the modal) is acceptable — if the user really needs this, they can navigate to `/booking/schedule` directly without going through the dashboard modal. No change needed.

---

### Refinement 3: Alpine.js `x-init` should respect `shared_room_active` to prevent deselection

> [!TIP]
> The schedule page's `bookingForm()` Alpine component initializes `selected` from `draft.type` (line 380). If the user navigated with `room_shared=1` and the prefilled type was forced to `computers_only`, the form works correctly.

But there's a potential issue: if the user **manually changes the type via browser devtools** or Alpine reactivity glitch, the `$watch('selected', ...)` will trigger `scheduleAvailabilityCheck()`, which calls the availability API. The API will correctly return `conflict` for incompatible types — and the submit button is disabled when `availStatus === 'conflict'` (line 352).

**Assessment:** The server-side gate (`createBooking()`) is intact. The availability API provides a second layer. The `disabled` attribute on the radio inputs provides the first layer. Three layers of defense — sufficient. No additional Alpine guard needed.

---

### Refinement 4: `shared_room_active` should not persist in session beyond the current draft

> [!NOTE]
> The `shared_room_active` flag gets stored in `booking_draft.schedule` session. When the user completes or abandons the booking, `session()->forget('booking_draft')` is called (line 255 in `BookingController::store()`). This correctly cleans up the flag.

However, if the user **navigates away** from the booking flow without completing it (e.g., clicks "Batal" → goes to dashboard → starts a new booking from a non-shared slot), the stale `shared_room_active = true` could persist in the session draft.

**Assessment:** This is already handled — when the user starts a new booking from the dashboard modal, `showSchedule()` detects the query params (line 170) and **overwrites** the entire `$prefill` array including `shared_room_active`. If the new slot has `room_shared=0` (or no param), `$request->boolean('room_shared')` returns `false`. The stale flag is overwritten. ✅

But if the user navigates **directly** to `/booking/schedule` (via the header "Buat Reservasi" button at line 157), no query params are present → `showSchedule()` skips the prefill block → loads the existing session draft → stale `shared_room_active = true` could persist.

**Recommendation:** Add a "Reset" mechanism. When the user lands on `/booking/schedule` without query params and there's an existing draft with `shared_room_active = true`, consider clearing it. Or simpler: the "Buat Reservasi" header button could include `?reset=1` to force-clear the session draft:

```php
if ($request->boolean('reset')) {
    session()->forget('booking_draft');
}
```

This is a minor edge case — the availability API would still correctly block incompatible submissions — but it prevents UX confusion where the user sees disabled options on a fresh booking form.

---

## Risk Assessment

| Change | Risk | Notes |
|--------|------|-------|
| `currentSlotIsSharedRoom` variable + `navigateToBooking()` URL param | **Low** | Additive JS change |
| `shared_room_active` in `$prefill` | **Low** | One line added to array |
| Disabled radio inputs in `schedule.blade.php` | **Low** | Visual-only; availability API and `createBooking()` are unchanged |
| `sharedRoomModal` / `sharedRoom` split in `renderTimeSlots()` | **Medium** | Must ensure `sharedRoomModal` is correctly passed to both `openSlotModal()` and `navigateToBooking()` — verify the variable is accessible in `navigateToBooking()` scope |
| Teal info banner on schedule page | **Low** | Additive Blade change |

---

## Implementation Scope

| Item | Complexity | Ready? |
|------|-----------|--------|
| `currentSlotIsSharedRoom` module variable | Low | ✅ Yes |
| `navigateToBooking()` URL param addition | Low | ✅ Yes |
| `shared_room_active` in `BookingController::showSchedule()` | Low | ✅ Yes |
| Disabled `full_room` radio + CSS in `schedule.blade.php` | Low | ✅ Yes |
| Disabled `room_only` radio (or just `exclusive` sub-option) | Low | ✅ Yes — see Refinement 1 |
| Teal info banner in `schedule.blade.php` | Low | ✅ Yes |
| `sharedRoomModal` / `sharedRoom` split in `renderTimeSlots()` | Medium | ✅ Yes |
| Pass `sharedRoomModal` to `openSlotModal()` click listener | Low | ✅ Yes |

**Total files changed: 3.** No new files, no model changes, no migration changes, no changes to `BookingService` or conflict logic.

---

## Summary

The plan correctly identifies both edge cases and proposes clean fixes. Key refinements:

1. **Consider allowing `room_only + shared` on the schedule page** — it's compatible per the conflict matrix. Only `full_room` and `room_only + exclusive` should be disabled.
2. **Edge Case 2 fix is correct as-is** — the `sharedRoomModal`/`sharedRoom` split is the right approach.
3. **Session staleness is mostly handled** — consider adding a `?reset=1` mechanism for the "Buat Reservasi" header button as a minor improvement.
4. **Three layers of defense exist** (disabled radio → availability API → `createBooking()`) — the fix adds the missing first layer without touching the existing server-side gates.
