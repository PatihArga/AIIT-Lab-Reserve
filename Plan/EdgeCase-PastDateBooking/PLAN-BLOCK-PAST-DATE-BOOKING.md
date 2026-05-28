### EC-I — Past Date Booking Block

**Plan ID:** EC-I
**Area:** Dashboard calendar · Booking schedule entry
**Drafted:** 2026-05-29
**Status:** Drafted — pending execution
**Risk:** Low (additive UI guard + one defensive server check; no schema or data migration)

---

## 1. Problem

A user can navigate the dashboard calendar to a **past date** (a day before today within the current month, or any day in any prior month) and:

1. Click the past day → the time-slot strip renders for that date.
2. Click any slot → the slot modal opens with `Buat Reservasi Sesi Ini` enabled.
3. Click the button → the schedule page loads with the past date prefilled.
4. On submission, the backend rejects with `"Tanggal tidak boleh di masa lalu"` — but only after the user has filled in 3 more steps.

The flow is silently broken: the calendar pretends the date is bookable, then the validator slams the door at the end. The intent (per supervisor) is that past dates should be **view-only** (history mode) — the “make reservation” path must be blocked at the earliest possible touchpoint.

### Reproduction
1. Login as a lecturer; today = 29 May 2026.
2. Open `/dashboard`. Calendar shows May 2026.
3. Click day `26` (past). Slots render.
4. Click any slot → modal opens. Click `Buat Reservasi Sesi Ini`.
5. Schedule page loads with `date=2026-05-26`, the previous step indicator filled.
6. Try to submit → error banner appears: “Tanggal tidak boleh di masa lalu.” User has wasted 3 steps.

---

## 2. Root Cause

### 2a. Calendar grid does not flag past dates
[dashboard.blade.php:489-502](resources/views/dashboard.blade.php#L489-L502) renders each day cell. Today is tagged (`day-today`), Sundays are made non-clickable (`day-other`), but **past dates within the current month get no special treatment** — they receive a normal click handler.

```js
if (isToday)  el.classList.add('day-today');
if (hasResv)  el.classList.add('day-has-event');
if (d === selectedDay && onCurrentMonth) el.classList.add('day-selected');
if (isSunday) el.classList.add('day-other');
else el.addEventListener('click', () => selectDay(d));   // ← past dates land here
```

Similarly, **prior months** (e.g., navigating with the `‹` arrow to April 2026) render every day as clickable.

### 2b. Modal `Buat Reservasi` button has no date-aware gate
[dashboard.blade.php:446-449](resources/views/dashboard.blade.php#L446-L449) — `#modal-reserve-btn` is always enabled. There is no check that the chosen `(calYear, calMonth, modalDay)` is in the future.

### 2c. Schedule page accepts past-date prefill from URL
[BookingController.php:186](app/Http/Controllers/BookingController.php#L186) reads `?date=` into the session draft without checking past-vs-future. The eventual `after_or_equal:today` validator fires only at **POST** time, not when the page loads. So if a user reaches the page with a past date (e.g., via a stale browser back-stack, hand-edited URL, or any future regression in the calendar gate), the page paints over the bad state.

---

## 3. Scope

### In scope
- Dashboard calendar: visually disable past days; no click handler.
- Dashboard slot modal: disable `Buat Reservasi Sesi Ini` if the active day is past (defense-in-depth; should never fire after fix 1, but guards against future regressions / direct DOM manipulation).
- Schedule page: when `?date=<past>` arrives, drop the date from the prefill and surface a flash message, so the user lands on a clean form instead of a poisoned one.
- Preserve the **view** of past dates? **NO** — supervisor wants past dates fully non-interactive on the dashboard calendar. History is already viewable via the Riwayat (history) page.

### Out of scope
- Admin calendar views (no admin calendar exists; admin uses `/admin/requests`).
- The booking-history page (`/booking/history`) — history is read-only by design.
- Admin approval workflow (admins already have `S8` guard against approving past bookings — [AdminRequestController.php:89](app/Http/Controllers/Admin/AdminRequestController.php#L89)).
- Changing time-zone logic. The existing `TODAY` constant in dashboard is built from `now()->year/month/day` server-side, so this fix inherits whatever TZ the app is configured for (Asia/Jakarta).

---

## 4. Design

### 4a. Calendar grid — make past dates `day-other`-equivalent
Compute `isPast` per day; merge into the existing `day-other` branch.

```js
// new line: stable midnight reference for the user's "today"
const todayMidnight = new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate());

for (let d = 1; d <= daysInMonth; d++) {
    const cellDate = new Date(calYear, calMonth, d);
    const isToday  = cellDate.getTime() === todayMidnight.getTime();
    const isPast   = cellDate < todayMidnight;
    const hasResv  = onCurrentMonth && USER_EVENTS[d] && USER_EVENTS[d].length > 0;
    const isSunday = cellDate.getDay() === 0;
    // ...
    if (isToday)  el.classList.add('day-today');
    if (hasResv)  el.classList.add('day-has-event');
    if (d === selectedDay && onCurrentMonth) el.classList.add('day-selected');
    if (isSunday || isPast) el.classList.add('day-other');   // ← merged
    else el.addEventListener('click', () => selectDay(d));
}
```

**Why merge with `day-other`?** The existing class already paints the cell faded and provides the “not clickable” visual cue (Sundays use it). Reusing the same class avoids new CSS surface area and keeps the visual language consistent (faded = unavailable).

If we want a distinct shade for past vs. Sunday vs. other-month days later, that’s a follow-up cosmetic task — not blocked by this plan.

### 4b. Modal `Buat Reservasi` button — defense-in-depth
[dashboard.blade.php:647 `openSlotModal`](resources/views/dashboard.blade.php#L647): after computing modal date, check past and disable the button + show a banner.

```js
const slotDate     = new Date(calYear, calMonth, day);
const todayMid     = new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate());
const isPastSlot   = slotDate < todayMid;

const reserveBtn = document.getElementById('modal-reserve-btn');
reserveBtn.disabled = isPastSlot;
reserveBtn.classList.toggle('is-disabled', isPastSlot);
reserveBtn.style.opacity = isPastSlot ? '0.45' : '';
reserveBtn.style.cursor  = isPastSlot ? 'not-allowed' : '';
reserveBtn.title         = isPastSlot ? 'Tanggal sudah lewat — tidak dapat dipesan' : '';
```

Optionally surface a small inline note above the action row (re-use the same amber banner pattern as `modal-computer-banner`):

```js
// Banner element id: 'modal-past-banner' — created lazily, same pattern as modal-computer-banner
// "Slot ini sudah berlalu. Reservasi hanya bisa dibuat untuk hari ini dan seterusnya."
```

Then in `navigateToBooking()` add a hard guard so the function aborts if called somehow (e.g., enter key, dev tools):

```js
function navigateToBooking() {
    if (!modalDay || !modalSlot) return;
    const slotDate = new Date(calYear, calMonth, modalDay);
    const todayMid = new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate());
    if (slotDate < todayMid) return;   // hard stop
    // ...existing body...
}
```

### 4c. Schedule page — defensive prefill drop
[BookingController.php showSchedule](app/Http/Controllers/BookingController.php) — when reading `$request->input('date')` for prefill, check past:

```php
$prefillDate = $request->input('date');
if ($prefillDate) {
    try {
        $parsed = Carbon::createFromFormat('Y-m-d', $prefillDate);
        if ($parsed->isPast() && !$parsed->isToday()) {
            $prefillDate = null;
            session()->flash('error', 'Tanggal yang dipilih sudah lewat. Silakan pilih tanggal hari ini atau setelahnya.');
        }
    } catch (\Throwable $e) {
        $prefillDate = null;  // malformed input — let the form land empty
    }
}
```

This guard runs even if the dashboard fix is bypassed (URL hand-edit, stale bookmark, future regression). It produces a clean form with a clear flash message instead of a poisoned form that fails on submit.

**Note:** the `min` attribute on the schedule page's date input — there isn't one today because the date field is `<input type="hidden">` ([schedule.blade.php:79](resources/views/booking/schedule.blade.php#L79)). The date is set entirely by Alpine state from the URL prefill. So the server-side prefill drop in `showSchedule` is the right place to enforce; nothing to add at the input level.

---

## 5. Step-by-step execution

| # | File | Change | Verification |
|---|------|--------|--------------|
| 1 | [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php) `renderCalendar()` (around L489-502) | Add `todayMidnight` const; compute `cellDate` and `isPast` per day; merge `isPast` into `day-other` branch | Reload `/dashboard`; navigate calendar to current month — yesterday and earlier are faded and non-clickable; today and future are unchanged. Click `‹` to April → all days faded. |
| 2 | [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php) `openSlotModal()` (around L647) | Compute `isPastSlot`; toggle `#modal-reserve-btn` disabled + opacity + cursor + title; optionally insert `#modal-past-banner` | Manually call `selectDay(<past>)` from devtools, then click a slot — button is greyed, banner shown. |
| 3 | [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php) `navigateToBooking()` (around L839) | Add early-return when `slotDate < todayMid` | Dev tools: call `navigateToBooking()` after forcing past `modalDay` — no navigation occurs. |
| 4 | [app/Http/Controllers/BookingController.php](app/Http/Controllers/BookingController.php) `showSchedule()` (around L186) | Parse `?date=`; if past, set to `null` and `session()->flash('error', ...)` | Hit `/booking/schedule?date=2026-05-26` directly — form loads with date empty, red flash banner visible. |
| 5 | Smoke — no regression | — | (a) Today + future days still clickable, slots still bookable. (b) Sundays still grey. (c) Other-month days still grey. (d) Header "Buat Reservasi" button still works (it carries no date, so no prefill drop). (e) `?room_shared=1` and `?computer_booked=1` flows still preserve flags. |

No migrations. No service changes. No new routes. No package changes.

---

## 6. Acceptance criteria

- [ ] On the dashboard calendar, every day strictly before today (across all months including the current month) is rendered faded and **does not** open the slot strip on click.
- [ ] Today and every future day are unchanged — clickable, modal works, reservation flow works.
- [ ] If a user reaches the slot modal for a past date through any path (manual dev-tools call, stale state, future regression), `Buat Reservasi Sesi Ini` is visibly disabled with a tooltip/banner explaining why, and `navigateToBooking()` is a no-op.
- [ ] Hitting `/booking/schedule?date=2026-05-26` (or any past date) loads the form without the past date prefilled, with a flash error stating the date is in the past.
- [ ] Submitting a booking with a past date (POST `/booking/schedule`) still rejects with the existing `Tanggal tidak boleh di masa lalu.` validator message — unchanged behavior, kept as the final guard.
- [ ] No change to: header "Buat Reservasi" button, history page, admin pages, type restriction banners (`room_shared`, `computer_booked`), modal sharing logic.

---

## 7. Test scenarios

| # | Scenario | Expected |
|---|----------|----------|
| T1 | Click yesterday on current-month calendar | Cell is faded, no slot strip, no modal |
| T2 | Click today on current-month calendar | Slot strip renders normally; modal works |
| T3 | Click tomorrow on current-month calendar | Slot strip renders normally; modal works |
| T4 | Navigate to previous month via `‹` and click any day | All days faded, none clickable |
| T5 | Navigate to next month via `›` and click any day | All days normal; clickable |
| T6 | DevTools: `selectDay(yesterday's day number)` then click a slot | Modal opens, but `Buat Reservasi Sesi Ini` is disabled + banner shown |
| T7 | DevTools: with past-date modal state, call `navigateToBooking()` | No navigation; no URL change |
| T8 | Direct URL `/booking/schedule?date=<yesterday>` | Form loads with `date` empty, red flash error |
| T9 | Direct URL `/booking/schedule?date=<today>` | Form loads with today prefilled, no flash error |
| T10 | Direct URL `/booking/schedule?date=<future>` | Form loads with future date prefilled, no flash error |
| T11 | Sunday on a future month | Still faded (unchanged behavior) |
| T12 | A user-owned booking on a past date (e.g., yesterday's "Saya") | Still visible in history page. On dashboard calendar, the day is faded and not openable — consistent with view-only history |
| T13 | Page open at 23:59, today rolls over to tomorrow at 00:00 | TODAY constant was captured at page render; old "today" is still clickable until refresh. **Acceptable** — server-side validator catches the edge if user submits after midnight without refreshing |
| T14 | Cross-flag combination: navigate from a future shared-room slot | `room_shared=1` still flows correctly; date is future so prefill drop doesn't fire |

---

## 8. Files changed (summary)

| File | Lines (approx.) | Type |
|------|----------------|------|
| `resources/views/dashboard.blade.php` | +20 across 3 functions | JS — calendar grid, modal open, navigate guard |
| `app/Http/Controllers/BookingController.php` | +10 in `showSchedule()` | PHP — defensive prefill drop |

No new files. No deleted files. No migrations. No seeders.

---

## 9. Rollback strategy

All changes are additive UI/UX gates. To revert:

1. Remove the `isPast` computation and the merged `day-other` branch in `renderCalendar()`.
2. Remove the `isPastSlot` block in `openSlotModal()` and the optional `#modal-past-banner` creation.
3. Remove the early-return in `navigateToBooking()`.
4. Remove the `Carbon` parse / flash block in `showSchedule()`.

Backend validator (`after_or_equal:today`) is **unchanged** — the system's correctness floor stays the same with or without this fix. The fix is UX-quality.

---

## 10. After execution — handover update

Add `EC-I — Past date booking block (2026-05-29)` to Section 5 of `Plan/PROJECT-HANDOVER.md` with:
- Problem (1 sentence)
- Files touched (4 entries, matching the table in §8)
- Plan pointer: `Plan/EdgeCase-PastDateBooking/PLAN-BLOCK-PAST-DATE-BOOKING.md` (executed)
