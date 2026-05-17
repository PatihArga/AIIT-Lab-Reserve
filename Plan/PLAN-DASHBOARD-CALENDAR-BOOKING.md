# Plan: Dashboard Calendar → Booking Form Integration

**Status:** Not started  
**Scope:** 2 files, ~80 lines changed  
**Risk:** Low — no schema changes, no new routes

---

## 1. Problem Statement

The interactive calendar on the user dashboard (`/dashboard`) lets users browse time slots and opens a modal showing computer availability. The modal has a "Buat Reservasi Sesi Ini" button, but it currently:

1. Shows **fake computer availability** — `getComputerStates()` returns a simplified guess based only on whether *any* booking overlaps that hour, not real per-computer data.
2. Has **no computer selection state** — clicking a green "Tersedia" computer slot does nothing; no selection is tracked.
3. **Does not navigate anywhere** — the reserve button calls `closeSlotModal(null, true)`, which just closes the modal.

The infrastructure to fix all three is already in place:
- `GET /api/computers/available` endpoint returns real per-computer availability JSON.
- `BookingController::showSchedule()` reads `$draft` from session and pre-fills the schedule form.
- The schedule form (`booking/schedule.blade.php`) accepts `$draft` and pre-populates all fields on render.

---

## 2. What Will Change

| File | What Changes |
|---|---|
| `resources/views/dashboard.blade.php` | Replace dummy `getComputerStates()` with real API fetch; add computer selection state; wire reserve button to build URL + navigate |
| `app/Http/Controllers/BookingController.php` | `showSchedule()` reads GET params → writes to session → redirects to self (clean URL) |

No migrations, no new routes, no new files.

---

## 3. Type Mapping

The modal uses short names; the booking system uses enum values.

| Modal `data-type` | DB / form `type` |
|---|---|
| `computer` | `computers_only` |
| `both` | `full_room` |
| `room` | `room_only` |

This mapping is applied in JavaScript when building the redirect URL.

---

## 4. Data Flow (After Fix)

```
User clicks time slot
        │
        ▼
openSlotModal(day, slot)
  ├─ fetch /api/computers/available?date=&start_time=&end_time=
  ├─ render real computer grid (available / booked / maintenance)
  └─ clicking available PC toggles selectedPcIds[]
        │
User picks type + (optionally) computers → clicks "Buat Reservasi Sesi Ini"
        │
        ▼
buildReserveUrl() → window.location.href
  URL: /booking/create/schedule?type=computers_only&date=2026-05-18
       &start_time=09:00&end_time=10:00&computers[]=1&computers[]=3
        │
        ▼
BookingController::showSchedule(Request $request)
  ├─ detects GET params (type, date, start_time, end_time)
  ├─ builds $prefill array (skipping full validation — just basic parsing)
  ├─ session(['booking_draft.schedule' => $prefill])
  └─ redirect()->route('booking.schedule')   ← clean URL, no params
        │
        ▼
showSchedule() (second request, no GET params)
  └─ reads $draft from session → passes to view
        │
        ▼
booking/schedule.blade.php renders pre-filled
  ├─ type radio pre-selected
  ├─ date pre-selected in calendar (Alpine: draft.date)
  ├─ start_time / end_time selects pre-selected
  ├─ computers[] checkboxes pre-checked (via x-computer-grid :selected)
  └─ availability indicator auto-fires (scheduleAvailabilityCheck on init)
```

---

## 5. Implementation Steps

### Step 1 — `showSchedule()`: Accept GET params and seed session

**File:** `app/Http/Controllers/BookingController.php`  
**Current code (lines 88–94):**
```php
public function showSchedule(): View
{
    $computers = Computer::orderBy('unit_number')->get(['id', 'unit_number', 'label', 'status']);
    $draft     = session('booking_draft.schedule');
    return view('booking.schedule', compact('computers', 'draft'));
}
```

**Replace with:**
```php
public function showSchedule(Request $request): View|RedirectResponse
{
    // If the dashboard modal navigated here with prefill params, seed the session draft
    // and redirect to the clean URL so the form renders without query params.
    if ($request->hasAny(['type', 'date', 'start_time', 'end_time'])) {
        $typeMap = ['computer' => 'computers_only', 'both' => 'full_room', 'room' => 'room_only'];
        $rawType = $request->input('type', '');
        $prefill = [
            'type'         => $typeMap[$rawType] ?? $rawType,
            'date'         => $request->input('date', ''),
            'start_time'   => $request->input('start_time', ''),
            'end_time'     => $request->input('end_time', ''),
            'room_sharing' => $request->input('room_sharing'),
            'computers'    => array_map('intval', (array) $request->input('computers', [])),
        ];
        session(['booking_draft.schedule' => $prefill]);
        return redirect()->route('booking.schedule');
    }

    $computers = Computer::orderBy('unit_number')->get(['id', 'unit_number', 'label', 'status']);
    $draft     = session('booking_draft.schedule');
    return view('booking.schedule', compact('computers', 'draft'));
}
```

**Why redirect instead of render directly?**  
The schedule form is a GET form that submits to `booking.logbook`. If the user lands on the URL with query params and submits the form, the browser may confusingly preserve them. A redirect to the clean URL avoids this and keeps the URL bar clean.

**Why not call `validateSchedule()`?**  
The modal already shows only valid slots (future dates, lab hours). Full re-validation happens when the user submits the schedule form. The light prefill here is intentionally permissive — the form's own submit-path validation is the authoritative gate.

---

### Step 2 — `openSlotModal()`: Fetch real computer availability

**File:** `resources/views/dashboard.blade.php`

Add module-level state variables above `openSlotModal()` (currently at line 595). These need to be accessible by both `openSlotModal()` and the button click handler:

```js
let modalDay = null, modalSlot = null, selectedPcIds = [];
const COMPUTERS_AVAIL_URL = @json(route('api.availability.computers'));
```

Replace the current `openSlotModal(day, slot)` body. The function currently:
1. Sets date/time text in the modal header.
2. Calls `getComputerStates(day, slot.startHour)` for dummy data.
3. Renders the computer grid synchronously.

**New version** (async, fetches real data):

```js
async function openSlotModal(day, slot) {
    // Store for use by the reserve button
    modalDay = day; modalSlot = slot; selectedPcIds = [];

    // Update modal header
    const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    document.getElementById('modal-date-text').textContent =
        dayNames[new Date(calYear, calMonth, day).getDay()] + ', ' + day + ' ' + MONTHS_ID[calMonth] + ' ' + calYear;
    const fmt = m => String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
    document.getElementById('modal-time-title').textContent = fmt(slot.startMin) + ' — ' + fmt(slot.endMin);

    // Reset type/sharing UI
    currentResType = 'computer'; currentSharing = 'exclusive';
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelector('.type-card[data-type="computer"]').classList.add('active');
    document.querySelectorAll('.sharing-btn').forEach(o => o.classList.remove('active'));
    document.querySelector('.sharing-btn[data-val="exclusive"]').classList.add('active');
    document.getElementById('modal-sharing-row').style.display = 'none';
    document.getElementById('modal-computers-section').style.display = 'block';
    document.getElementById('modal-computer-label').textContent = 'Pilih unit komputer';

    // Show skeleton while fetching
    const computers = document.getElementById('modal-computers');
    computers.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px 0;color:rgba(10,26,71,.4);font-size:12px;">Memuat ketersediaan…</div>';
    document.getElementById('modal-reserve-btn').disabled = true;
    document.getElementById('slot-modal-overlay').classList.add('open');

    // Fetch real availability
    const mm = String(calMonth + 1).padStart(2, '0');
    const dd = String(day).padStart(2, '0');
    const dateStr = calYear + '-' + mm + '-' + dd;
    const params = new URLSearchParams({
        date: dateStr,
        start_time: fmt(slot.startMin),
        end_time: fmt(slot.endMin),
    });

    let computerList;
    try {
        const res = await fetch(COMPUTERS_AVAIL_URL + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('http ' + res.status);
        const data = await res.json();
        computerList = data.computers;
    } catch (e) {
        computers.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px 0;color:#dc2626;font-size:12px;">Gagal memuat data. Coba lagi.</div>';
        return;
    }

    renderModalComputers(computerList);
    document.getElementById('modal-reserve-btn').disabled = false;
}
```

---

### Step 3 — `renderModalComputers()`: New helper to render the grid

Extract the computer rendering into its own function so it can be called again when the user changes the booking type. Add this function alongside `openSlotModal()`:

```js
function renderModalComputers(computerList) {
    const computers = document.getElementById('modal-computers');
    computers.innerHTML = '';
    let avail = 0, booked = 0, maint = 0;

    computerList.forEach((c, i) => {
        const el = document.createElement('div');
        const isAvail = c.available;
        const isMaint = c.status !== 'online';
        const isBooked = !isAvail && !isMaint;

        const cls = isAvail ? 'slot-available' : isMaint ? 'slot-maintenance-pc' : 'slot-booked-pc';
        el.className = 'computer-slot ' + cls;
        el.dataset.pcId = c.id;
        el.style.animationDelay = (i * 55) + 'ms';

        const lbl = isAvail ? 'Tersedia' : isMaint ? 'Perawatan' : 'Terpakai';
        el.innerHTML = '<div class="slot-monitor"></div>'
                     + '<div class="slot-num">' + c.label + '</div>'
                     + '<div class="slot-status-text">' + lbl + '</div>';

        if (isAvail) {
            avail++;
            el.addEventListener('click', () => togglePcSelection(el, c.id));
        } else if (isMaint) {
            maint++;
        } else {
            booked++;
        }

        computers.appendChild(el);
    });

    document.getElementById('modal-summary').innerHTML =
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:#2eb8a0"></div><span class="font-bold text-ink-900">' + avail + '</span>&nbsp;<span class="text-ink-700/40">tersedia</span></div>' +
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:#F5B800"></div><span class="font-bold text-ink-900">' + booked + '</span>&nbsp;<span class="text-ink-700/40">terpakai</span></div>' +
        '<div class="flex items-center gap-1.5 flex-1"><div class="w-2 h-2 rounded-full shrink-0" style="background:rgba(15,36,96,.14)"></div><span class="font-bold text-ink-900">' + maint + '</span>&nbsp;<span class="text-ink-700/40">perawatan</span></div>';
}
```

---

### Step 4 — `togglePcSelection()`: Computer selection in modal

When type is `computers_only`, the user selects specific computers. This function toggles selection visually and in `selectedPcIds[]`:

```js
function togglePcSelection(el, pcId) {
    if (currentResType !== 'computer') return;
    const idx = selectedPcIds.indexOf(pcId);
    if (idx === -1) {
        selectedPcIds.push(pcId);
        el.style.outline = '2px solid #2eb8a0';
        el.style.outlineOffset = '2px';
    } else {
        selectedPcIds.splice(idx, 1);
        el.style.outline = '';
        el.style.outlineOffset = '';
    }
}
```

The teal outline ring matches the `slot-available` border color, giving clear selected feedback without introducing a new CSS class.

---

### Step 5 — `selectResType()`: Refresh computer label + re-enable selection

The existing `selectResType()` function already shows/hides the computers section and sharing row. Add one line to reset `selectedPcIds` when the type changes, since a different type means a different computer selection context:

```js
function selectResType(type) {
    selectedPcIds = [];   // ← ADD THIS LINE at the top
    currentResType = type;
    // ... rest of existing function unchanged ...
}
```

---

### Step 6 — Wire the "Buat Reservasi Sesi Ini" button

The button is at dashboard.blade.php line 435:
```html
<button ... id="modal-reserve-btn" onclick="closeSlotModal(null, true)">
```

Change the `onclick` to call a new `navigateToBooking()` function:
```html
<button ... id="modal-reserve-btn" onclick="navigateToBooking()">
```

Add the `navigateToBooking()` function:
```js
function navigateToBooking() {
    if (!modalDay || !modalSlot) return;

    const typeMap = { computer: 'computers_only', both: 'full_room', room: 'room_only' };
    const fmt = m => String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
    const mm = String(calMonth + 1).padStart(2, '0');
    const dd = String(modalDay).padStart(2, '0');

    const params = new URLSearchParams();
    params.set('type', typeMap[currentResType] || currentResType);
    params.set('date', calYear + '-' + mm + '-' + dd);
    params.set('start_time', fmt(modalSlot.startMin));
    params.set('end_time', fmt(modalSlot.endMin));

    if (currentResType === 'room') {
        params.set('room_sharing', currentSharing);
    }
    if (currentResType === 'computer' && selectedPcIds.length > 0) {
        selectedPcIds.forEach(id => params.append('computers[]', id));
    }

    // The base path must match the Laravel route for /booking/create/schedule.
    // Using a server-rendered URL avoids hardcoding the path prefix.
    const base = @json(route('booking.schedule'));
    window.location.href = base + '?' + params.toString();
}
```

> **Note on `@json(route('booking.schedule'))`**: This must be inside the `@push('scripts')` block where Blade templating is active. It evaluates to the full URL string at render time, e.g., `"http://localhost/UKRIDA_LabReserve/public/booking/create/schedule"`. This avoids hardcoding the XAMPP subdirectory prefix.

---

### Step 7 — Remove `getComputerStates()`

The `getComputerStates()` function (lines 451–463) is now replaced by the API fetch. Delete it. This also removes the only internal caller since `openSlotModal()` no longer calls it.

---

## 6. Edge Cases

| Scenario | Handling |
|---|---|
| User opens modal for a slot on a non-current month | `calYear`/`calMonth` already hold the navigated month, so the date built in `openSlotModal()` is correct regardless of today's date. |
| API request fails (network error, 500) | Error message shown in the computer grid area; reserve button remains disabled. |
| User selects type `both` (Ruang+Komputer) | `selectedPcIds` is irrelevant — `full_room` implies all computers. No computers are passed as GET params. |
| User selects type `room` (Ruang Saja) | No computers section shown. `room_sharing` is passed as GET param. |
| Prefill data fails `validateSchedule()` in a future request | The schedule form's own submit guard catches this. The prefill step is intentionally non-validating. |
| User navigates back to dashboard without submitting the form | `session('booking_draft.schedule')` persists. Re-opening the modal and clicking reserve will overwrite it. This is acceptable behavior. |
| `computers_only` but no computers selected | URL is built without `computers[]` params. The schedule form will still render but the availability indicator will say "Pilih minimal 1 unit" on submit. Acceptable — the form itself validates this. |

---

## 7. Files Changed Summary

### `app/Http/Controllers/BookingController.php`
- **`showSchedule()`**: Add `Request $request` param; add GET param detection block (type mapping + session write + redirect); keep existing render path for the clean URL case.

### `resources/views/dashboard.blade.php`
- **Delete** `getComputerStates()` function (lines 451–463).
- **Add** `modalDay`, `modalSlot`, `selectedPcIds`, `COMPUTERS_AVAIL_URL` module-level variables.
- **Replace** `openSlotModal()` body with async version that fetches real data.
- **Add** `renderModalComputers()` helper.
- **Add** `togglePcSelection()` helper.
- **Add** one line to top of `selectResType()` to reset `selectedPcIds`.
- **Replace** `onclick="closeSlotModal(null, true)"` on the reserve button with `onclick="navigateToBooking()"`.
- **Add** `navigateToBooking()` function.

---

## 8. Success Criteria

1. Clicking a time slot opens the modal and the computer grid shows real data from the API (green/amber/grey based on actual bookings at that time).
2. Clicking a green computer in `Komputer` mode outlines it in teal; clicking again deselects. Multiple selection works.
3. Switching to `Ruang + Komputer` or `Ruang Saja` hides/shows the computers section correctly, as before.
4. Clicking "Buat Reservasi Sesi Ini":
   - Navigates to `/booking/create/schedule` (clean URL, no query params visible).
   - The schedule form renders with type, date, start/end time, and selected computers all pre-filled.
   - The availability indicator auto-fires and shows the slot status.
5. User can complete the 3-step form normally from this point.
6. If the API fetch fails, an inline error appears and the reserve button stays disabled (no broken navigation).
