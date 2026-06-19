# PLAN: Logbook Software-Installation Checkbox + Logbook Audit Logging

Two supervisor requests against the user **Logbook page** (`/logbook`):

1. **Software-installation checkbox** on each logbook card. Its default state
   (on/off) comes from what the user chose during the *reservation* step
   (`needs_installation`). When the box is checked, a new input area appears
   **below the notes canvas** for the user to list the software they
   downloaded/installed.
2. **Audit logging** — every time a user edits a logbook, write an entry to the
   admin audit log.

---

## Current State (verified)

### Database — `booking_logbooks` (migration `2024_01_01_000006`)
The two columns we need **already exist** — no migration is required:

| Column | Type | Today | Reuse for |
|---|---|---|---|
| `needs_installation` | `boolean` nullable | Set at booking time from the calendar form | The checkbox state |
| `special_software`   | `text` nullable    | Declared but **never written** anywhere | The "software downloaded" list |
| `checkpoint_progress`| `text`             | Edited on the logbook page today | (unchanged) |

`special_software` is in `BookingLogbook::$fillable` already
([BookingLogbook.php:9-14](../app/Models/BookingLogbook.php#L9-L14)) and a grep
shows it is referenced **only** in the model + migration (never read or written
in any controller/view) — so it is free to repurpose.

### Booking flow seeds `needs_installation`
[CalendarController.php:176](../app/Http/Controllers/CalendarController.php#L176)
saves `'needs_installation' => (bool) ($data['needs_installation'] ?? false)`
from the calendar's hidden input. So `$booking->logbook->needs_installation`
**already holds the reservation-time choice** → the checkbox default is free.

### Logbook page
- Controller [LogbookController.php:25-44](../app/Http/Controllers/LogbookController.php#L25-L44):
  `update()` validates and saves **only** `checkpoint_progress`; **no audit call**.
- View [logbook/index.blade.php:64-86](../resources/views/logbook/index.blade.php#L64-L86):
  one `<form>` per card with the auto-grow notes textarea + "Simpan Catatan".

### Audit infrastructure
- [AuditLogService::record()](../app/Services/AuditLogService.php) — static:
  `record($action, ?$auditable, $old = [], $new = [])`. Empty arrays stored as null.
- Action → human label/colour map in
  [AdminAuditLogController.php:17-30](../app/Http/Controllers/Admin/AdminAuditLogController.php#L17-L30).
  A `Booking` auditable already renders its `booking_code` as the "target"
  ([AdminAuditLogController.php:70-77](../app/Http/Controllers/Admin/AdminAuditLogController.php#L70-L77)),
  which is exactly what we want for a logbook edit.

---

## Design Decisions

- **No migration.** Reuse `needs_installation` (checkbox) + `special_software`
  (software list). *(Open decision A below if a dedicated column is preferred.)*
- **The logbook checkbox writes back to `needs_installation`.** It is *seeded*
  from the reservation choice on first view, but the logbook is the actual
  report, so a user toggling it there updates the stored value. After the first
  save the field naturally reflects the last saved state.
- **Software field is conditionally required**: required (min length) only when
  the checkbox is on; ignored/cleared when off.
- **Audit action name:** `logbook.updated`, auditable = the `Booking` (so the
  audit row's target shows the booking code). Log only when something actually
  changed (use the model's dirty/changed tracking) to avoid noise.

---

## Phase 1 — Software-installation checkbox + software field

### 1a. Backend — `LogbookController::update()`
- Add an Alpine-friendly boolean input `needs_installation` and a text input
  `special_software` to validation:
  - `needs_installation` → `['nullable', 'boolean']` (hidden input emits `1`/`0`).
  - `special_software` → `['nullable', 'string', 'max:2000']`, **required when**
    `needs_installation` is truthy: `'required_if:needs_installation,1'`.
- Normalise before save:
  - `$needsInstall = (bool) $request->boolean('needs_installation');`
  - When off → force `special_software = null` (don't keep stale text).
- Persist via the existing `updateOrCreate` call, merging the new fields with
  `checkpoint_progress` + the `category` fallback that's already there.
- Indonesian validation messages, matching the existing style.

### 1b. Frontend — `logbook/index.blade.php`
Inside each card's `<form>`, wrap in `x-data` seeded from the stored value:

```blade
<form ... x-data="{ install: {{ old('needs_installation', $booking->logbook->needs_installation ?? false) ? 'true' : 'false' }} }">
```

- Keep the existing notes canvas (row 2) unchanged.
- **Below the canvas**, add the checkbox row:
  - A styled checkbox bound `x-model="install"` + label
    "Instalasi perangkat lunak" (matches request).
  - A hidden input `name="needs_installation" :value="install ? '1' : '0'"` so
    the boolean always posts.
- **Conditional software block** (`x-show="install"`, `x-collapse` or simple
  transition): a labelled textarea `name="special_software"`
  (placeholder "Tuliskan perangkat lunak yang diunduh/diinstal…"), prefilled
  with `old('special_software', $booking->logbook->special_software)`.
  - `:required="install"` so the browser enforces it only when shown.
- Keep one "Simpan Catatan" submit that posts all three fields together.

### 1c. Verify Phase 1
- `php -l` the controller; `view:clear`.
- Runtime check (bootstrap script): seed a logbook with
  `needs_installation = true`, render `/logbook`, assert the checkbox is checked
  and the software textarea is visible; seed `false`, assert hidden/unchecked.
- POST with box on + empty software → expect validation error; with text → saved.
- POST with box off → `special_software` stored as null.

---

## Phase 2 — Audit logging on logbook edit

### 2a. `LogbookController::update()`
- Before saving, snapshot the old values of the three tracked fields
  (`checkpoint_progress`, `needs_installation`, `special_software`) from the
  existing logbook (may be null on first creation).
- After `updateOrCreate`, compare; if any tracked field changed, call:

```php
AuditLogService::record('logbook.updated', $booking, $old, $new);
```

  where `$old`/`$new` contain only the changed fields (compact diff). For long
  `checkpoint_progress`, store a trimmed preview (e.g. first ~120 chars) to keep
  audit rows small — *(open decision B)*.
- First-time creation (no prior logbook) counts as a change → logged as well.

### 2b. `AdminAuditLogController::PRESENTATION`
Add a label + dot colour so the entry renders nicely:

```php
'logbook.updated' => ['Logbook diperbarui', 'bg-mark-500'],
```

(The `Booking` auditable already makes the target column show the booking code.)

### 2c. Verify Phase 2
- Runtime: edit a logbook, assert one `audit_logs` row with
  `action = logbook.updated`, correct `user_id`, `auditable_type = Booking`,
  `auditable_id`, and a non-empty diff; re-save with no changes → **no** new row.
- Load `/admin/audit-log`, confirm the row shows "Logbook diperbarui" + the
  booking code as target. Clean up test rows afterward.

---

## Files Touched

| File | Change |
|---|---|
| `app/Http/Controllers/LogbookController.php` | Validation + save for `needs_installation`/`special_software`; audit call |
| `resources/views/logbook/index.blade.php` | Checkbox + conditional software textarea (Alpine) |
| `app/Http/Controllers/Admin/AdminAuditLogController.php` | `logbook.updated` presentation entry |

**No migration. No route changes. No model change** (`special_software` already fillable).

---

## Open Decisions (please confirm before implementation)

- **A. Storage column for the software list.**
  Recommended: **reuse `special_software`** (no migration, semantically close).
  Alternative: add a clearly-named `installed_software` column via a new
  migration.
- **B. Audit detail granularity.**
  Recommended: store a **compact diff of changed fields**, with
  `checkpoint_progress` truncated to a short preview.
  Alternatives: store full before/after text, or log a metadata-only entry
  (no field values).
- **C. Log only on real changes vs. every save.**
  Recommended: **only when a tracked field actually changed.**
  Alternative: an entry on every successful submit.

---

## Out of Scope
- Changing the reservation/booking form (`needs_installation` capture there is
  already correct and unchanged).
- Admin-side editing of logbooks.
- Exposing `special_software` on the admin request-detail view (can be a
  follow-up if the supervisor wants admins to see the installed-software list).
