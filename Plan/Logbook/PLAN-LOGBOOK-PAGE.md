# PLAN — Logbook Page

**Date:** 2026-06-14
**Status:** PLANNED — not yet executed (plan only; no code until approved)
**Reference:** user-provided screenshot — a card with a header strip (code · type · date · time) and a "CATATAN LOGBOOK" free-notes area.

---

## 1. Goal

A dedicated **Logbook** page for users (lecturer / team) that lists their **approved** reservations as cards. Each card has:
- **Row 1 — booking header:** booking code, type badge, date, time range.
- **Row 2 — "CATATAN LOGBOOK":** a free-text area the user fills in / edits and saves.

Add a **"Logbook"** item to the **user top navigation** (top nav only — admin keeps its sidebar).

---

## 2. Current state (reuse, don't rebuild)

The logbook concept already exists:
- Every booking has exactly one `booking_logbooks` row (created at booking time; `hasOne`). Its main free-text field is **`checkpoint_progress`** (set from the booking "Alasan / Tujuan").
- `booking/show.blade.php` already includes `_logbook-form.blade.php` to edit the logbook (checkpoint_progress + session_target + supervisor + course) **when status is `approved` or `completed`** (`Booking::isEditable()`).
- `BookingLogbookController::update` (route `booking.logbook.update`, `PUT /booking/{booking}/logbook`) validates + saves it and is owner-guarded (`abort_if($booking->user_id !== auth()->id())`).

So the new Logbook page is essentially a **consolidated list + quick editor** for the same per-booking logbook text.

---

## 3. Decisions to confirm (before coding)

| # | Decision | Options | Recommendation |
|---|----------|---------|----------------|
| **D1** | Where do the free notes live? | **A)** Reuse the existing `booking_logbooks.checkpoint_progress` (no migration; one source of truth — the booking detail page and this page edit the same text; the box is pre-filled with the booking reason). **B)** Add a new `booking_logbooks.notes` text column (a truly *blank* canvas, separate from the booking reason; needs a migration). | **A — reuse `checkpoint_progress`.** No migration, unifies the logbook, avoids two competing logbook texts. If you specifically want a *blank* canvas separate from the booking reason, choose B. |
| **D2** | Which statuses appear? | Approved only · **Approved + Completed** | **Approved + Completed.** You said "only approved," and this still excludes pending/rejected/cancelled — but including `completed` means a booking's logbook doesn't vanish the moment an admin marks the session done. (Also matches the existing `isEditable()` rule.) Say the word if you want strictly `approved`. |

> The plan below assumes **A** + **approved & completed**. Both are easy to flip.

---

## 4. Data flow

```
/logbook  (GET, auth+active)  → LogbookController@index
  • $bookings = auth user's bookings, status in [approved, completed],
                with('logbook','computers'), latest by date, paginated
  • view: logbook/index.blade.php
      └ for each booking → a card:
          Row 1: code · type badge · date · time
          Row 2: <form PUT /logbook/{booking}> textarea(checkpoint_progress) + Simpan </form>

/logbook/{booking} (PUT, auth+active) → LogbookController@update
  • abort_if not owner; abort_if not isEditable()
  • validate notes (required, min:10, max:2000)
  • $booking->logbook()->update(['checkpoint_progress' => …])   // touches ONLY the note
  • redirect()->route('logbook.index')->with('success', …)
```

> Using a **dedicated `LogbookController::update`** (instead of the existing `booking.logbook.update`) keeps this page's save narrow — it updates only the note and never overwrites the other logbook fields (session_target, supervisor, course). The existing booking-detail logbook form stays as-is.

---

## 5. Files

### New
| File | Purpose |
|------|---------|
| `app/Http/Controllers/LogbookController.php` | `index()` (list) + `update()` (save one note) |
| `resources/views/logbook/index.blade.php` | the page (card list matching the screenshot) |

### Edited
| File | Change |
|------|--------|
| `routes/web.php` | add `GET /logbook` → `logbook.index`, `PUT /logbook/{booking}` → `logbook.update` (inside the `auth`+`active` group) |
| `resources/views/components/top-nav.blade.php` | add a **Logbook** nav item (book icon), active on `logbook.index` |

### (Only if D1 = B)
| File | Change |
|------|--------|
| `database/migrations/xxxx_add_notes_to_booking_logbooks.php` | nullable `notes` text column; the page edits `notes` instead of `checkpoint_progress` |

No other files. No change to booking creation, calendar, or admin.

---

## 6. Controller sketch

```php
class LogbookController extends Controller
{
    public function index(): View
    {
        $bookings = auth()->user()->bookings()
            ->whereIn('status', ['approved', 'completed'])   // D2
            ->with(['logbook', 'computers:id,label'])
            ->orderByDesc('date')->orderByDesc('start_time')
            ->paginate(10);

        return view('logbook.index', compact('bookings'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(! $booking->isEditable(), 403, 'Logbook reservasi ini belum dapat diubah.');

        $validated = $request->validate([
            'checkpoint_progress' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'checkpoint_progress.required' => 'Catatan logbook wajib diisi.',
            'checkpoint_progress.min'      => 'Catatan minimal 10 karakter.',
        ]);

        $booking->logbook()->updateOrCreate(
            ['booking_id' => $booking->id],
            $validated + ['category' => $booking->logbook->category ?? 'lainnya'],
        );

        return back()->with('success', 'Catatan logbook ' . $booking->booking_code . ' tersimpan.');
    }
}
```

---

## 7. Card structure (matches the screenshot)

```
┌───────────────────────────────────────────────────────────────────┐
│ [LAB-0013]  ● Komputer Saja              📅 12 Jun 2026   🕘 09:00–11:00 │  ← Row 1 (header strip, bg-ink-50/40)
├───────────────────────────────────────────────────────────────────┤
│ CATATAN LOGBOOK                                                     │  ← Row 2
│ ┌───────────────────────────────────────────────────────────────┐ │
│ │ <textarea> … free notes … </textarea>                         │ │
│ └───────────────────────────────────────────────────────────────┘ │
│                                                   [ Simpan Catatan ]│
└───────────────────────────────────────────────────────────────────┘
```

- **Type badge** reuses the established label + colour mapping:
  `computers_only` → "Komputer Saja" (indigo), `full_room` → "Ruang + Komputer" (violet),
  `room_only`+`exclusive` → "Ruang Eksklusif" (teal), `room_only`+`shared` → "Ruang Berbagi" (amber).
- Date via `$b->date->translatedFormat('d M Y')`; time via `substr(start_time,0,5)`–`substr(end_time,0,5)`.
- Each card is its own `<form method="POST">` + `@method('PUT')` posting to `logbook.update`; success → `back()` with the existing toast.
- Built with `<x-app-layout>` (gives non-admins the new top nav automatically) + existing components (`x-page-header`, `x-section`, `form-textarea`, `btn-mark`).

---

## 8. Top-nav item

Add after "Riwayat" in `top-nav.blade.php` (`$links` array), book icon, active on `logbook.index`:
```php
['label' => 'Logbook', 'route' => 'logbook.index', 'icon' => $icoBook,
 'active' => str_starts_with($current, 'logbook')],
```
(Desktop + mobile menus both iterate `$links`, so one entry covers both.)

---

## 9. Edge cases
- **No approved/completed bookings** → friendly empty state ("Belum ada reservasi yang disetujui untuk dicatat.").
- **Ownership** → `index` only queries the auth user's bookings; `update` aborts 403 otherwise.
- **Editability** → `update` aborts if `! isEditable()` (defense in depth; index already filters).
- **Long lists** → paginated (10/page), newest first.
- **Validation error** → redirect back with `$errors`; the page shows the message near the offending card (or a top banner).

---

## 10. Acceptance criteria
1. User top nav shows **Logbook**, active when on `/logbook`; admin sidebar unchanged.
2. `/logbook` lists only the user's **approved (+ completed)** bookings, newest first, each as a card with code · type badge · date · time.
3. Each card's "CATATAN LOGBOOK" textarea is pre-filled with the saved note and editable.
4. Saving updates only that booking's note and returns to `/logbook` with a success toast.
5. A user cannot see or edit another user's logbook (403).
6. Empty state shows when there are no eligible bookings.
7. No regressions to booking creation, the calendar, the booking detail logbook form, or admin pages.

---

## 11. Files summary
| Action | File |
|--------|------|
| CREATE | `app/Http/Controllers/LogbookController.php` |
| CREATE | `resources/views/logbook/index.blade.php` |
| EDIT | `routes/web.php` (2 routes) |
| EDIT | `resources/views/components/top-nav.blade.php` (nav item) |
| (CREATE, only if D1=B) | migration adding `booking_logbooks.notes` |
