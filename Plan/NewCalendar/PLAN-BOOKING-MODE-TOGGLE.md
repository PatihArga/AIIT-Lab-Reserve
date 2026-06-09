# PLAN — Calendar "Booking Mode" Toggle (drag without card interference)

**Date:** 2026-06-07
**Status:** PLANNED — not yet executed
**Scope:** Frontend only — `resources/views/calendar/index.blade.php`. No backend / no DB changes.

---

## 1. Problem

On the week calendar, booking **cards** (`.wcal-ev`) are absolutely positioned over the day columns and carry `@mousedown.stop`. When a slot is occupied, the card sits on top of the time column and **swallows the mousedown**, so the user can't start a drag-to-create on or near an existing booking. The cards "cover the time column."

## 2. Desired behavior (the toggle)

Turn the toolbar **"+ Buat Reservasi"** button into a **mode switch** with two states:

### View mode (default — unchanged look)
- Events render as full detail cards: badge, label (e.g. `PC-07`), who, time.
- Clicking a card → details popover. Clicking a roll-up → group popover.
- Toolbar button reads **"+ Buat Reservasi"** (amber).

### Booking mode (after pressing the button)
- The button label changes to **"Batal"** (cancel) and restyles (neutral/outline).
- All existing cards become **bare colored blocks**: the type color fill + left bar, **no text/info**, and **non-interactive** (`pointer-events: none`) so they never block a drag.
  - Colors are the existing type colors — Komputer = indigo, Ruang + Komputer = **purple**, Ruang Eksklusif = **teal/green**, Ruang Berbagi = **orange**.
- The day columns become a **clear surface for dragging** a time range to create a booking. Drag → the existing create-form popover opens.
- Pressing **"Batal"** returns to View mode: button back to "+ Buat Reservasi", full detail cards restored.

### Flow
```
View mode ──[click "+ Buat Reservasi"]──► Booking mode
  • cards = bare colored blocks (no text, click-through)
  • drag a column → create-form popover → confirm → booking saved
  • cancel the popover → stay in Booking mode (try another slot)
Booking mode ──[click "Batal"]──► View mode (cards restored)
```

---

## 3. Design decision (please confirm)

**Drag-to-create will be gated to Booking mode only.** In View mode the calendar is for browsing (click cards for detail); to make a booking you press "+ Buat Reservasi" first. This is the cleanest match to the described flow and removes the card-vs-drag conflict entirely.

> Today, dragging also works in the default view (but is blocked by cards). After this change, the empty-column drag in View mode is removed — booking always starts from the button. If you'd rather keep drag working in View mode too, say so and I'll leave `onColMouseDown` ungated.

---

## 4. Implementation (single file: `calendar/index.blade.php`)

### 4.1 New Alpine state
Add one boolean to `weekCal()` (near the other UI state, ~line 565):
```js
bookingMode: false,
```

### 4.2 Toolbar button → toggle (replace the current `.wcal-newbtn`, ~line 205)
```blade
{{-- View mode: enter booking mode --}}
<button class="wcal-newbtn" x-show="!bookingMode" @click="enterBookingMode()">
    <svg ...plus icon...></svg>
    Buat Reservasi
</button>
{{-- Booking mode: cancel back to view --}}
<button class="wcal-cancelbtn" x-show="bookingMode" x-cloak @click="exitBookingMode()">
    <svg ...x icon...></svg>
    Batal
</button>
```
- `newBookingDefault()` is **removed** (no longer opens a popover from the toolbar).

### 4.3 New methods (replace `newBookingDefault`)
```js
enterBookingMode() {
    this.closeAll();          // close any details/group/create popovers + clear selection/block
    this.bookingMode = true;
},
exitBookingMode() {
    this.closeAll();
    this.bookingMode = false;
},
```
(`closeAll()` already exists.)

### 4.4 Bare-block rendering (CSS + class binding)
Bind a mode class on the canvas (line ~235):
```blade
<div class="wcal-canvas" :class="{ 'wcal-booking-mode': bookingMode }" :style="{ height: canvasH + 'px' }">
```
Add CSS in the `@push('styles')` block:
```css
/* Booking mode — events become bare, click-through colored blocks */
.wcal-booking-mode .wcal-ev { pointer-events: none; cursor: default; opacity: .9; }
.wcal-booking-mode .wcal-ev > div { display: none; }          /* hide all text content */
.wcal-booking-mode .wcal-ev.is-selected { box-shadow: none; } /* no selection ring in this mode */
.wcal-booking-mode .wcal-col.is-bookable { cursor: crosshair; } /* signal draggable surface */
```
- `pointer-events: none` is the key fix — the card no longer intercepts the mousedown, so it falls through to `.wcal-col`'s `onColMouseDown`. The existing `@mousedown.stop` on the card becomes a no-op (event never reaches it).
- Hiding `.wcal-ev > div` leaves only the colored background + left bar (the type color). The pending stripe (`.is-pending::after`) may remain as a subtle texture or be hidden — minor, will hide for a clean look:
```css
.wcal-booking-mode .wcal-ev.is-pending::after { display: none; }
```

### 4.5 Gate drag to Booking mode (`onColMouseDown`, ~line 743)
Add an early guard:
```js
onColMouseDown(e, day) {
    if (!this.bookingMode) return;          // ← only drag-create while in booking mode
    if (e.button !== 0 || !day.bookable) return;
    ...unchanged...
}
```

### 4.6 Popover behavior
- `enterBookingMode()` / `exitBookingMode()` both call `closeAll()` so no stale popover lingers.
- Drag → `openCreate()` (unchanged) → the create-form popover shows. Confirming submits to `calendar.booking.store` (unchanged). Canceling the popover (`creating = null`) leaves `bookingMode` **true** so the user can immediately drag another slot.

### 4.7 New button style
Add a `.wcal-cancelbtn` next to `.wcal-newbtn` in CSS (neutral/outline so it reads as "cancel", e.g. white bg, ink text, subtle border) — mirrors `.wcal-today` styling.

---

## 5. Edge cases / behavior table

| Situation | Booking mode behavior |
|-----------|----------------------|
| Past / Sunday (closed) column | Still non-draggable (`day.bookable` guard kept) — no create |
| Existing event under the drag | Renders as a click-through colored block; drag passes over it; server-side conflict rules still apply on submit |
| Roll-up ("+N") tiles | Also become bare neutral blocks, non-interactive |
| Pending bookings | Show their type color (stripe hidden for cleanliness) |
| Clicking an event in booking mode | Nothing (pointer-events none) — to view details, press "Batal" first |
| Week/Day navigation while in booking mode | Stays in booking mode; events re-render as bare blocks |
| Confirm a booking | Normal redirect to `/booking/{id}`; next calendar load is back in View mode |

---

## 6. What does NOT change
- `CalendarController`, `store()`, `BookingService`, conflict detection, slot restrictions (`hardBlocked` / `sharedRoom` / `computerBooked`) — all unchanged.
- The create-form popover, PC-availability AJAX, and submission flow — unchanged.
- View-mode card appearance, details popover, group popover — unchanged.
- Backend, routes, DB — untouched.

---

## 7. Acceptance criteria
1. Default load: button reads "+ Buat Reservasi"; cards show full detail; clicking a card opens details.
2. Click "+ Buat Reservasi" → button becomes "Batal"; every card collapses to a bare colored block (no text); colors match type (purple / green / orange / indigo).
3. In booking mode, dragging across a column **over an existing block** starts a selection (no longer blocked) and opens the create-form popover.
4. Past/Sunday columns remain non-draggable.
5. Canceling the create popover keeps booking mode active.
6. Click "Batal" → button returns to "+ Buat Reservasi"; full detail cards reappear; clicking a card opens details again.
7. No console errors; existing conflict banners still appear in the create popover.

---

## 8. File touched
| File | Change |
|------|--------|
| `resources/views/calendar/index.blade.php` | `bookingMode` state, toolbar toggle button + handlers, `wcal-booking-mode` class binding + CSS, drag gating in `onColMouseDown`, remove `newBookingDefault` |

**Estimated surface:** ~1 new state field, 2 small methods (replacing 1), 1 class binding, ~6 CSS lines, 1 guard line, toolbar button markup. No other files.
