# PLAN — Phase 8: Reports, Audit Log & Settings

**Date:** 2026-06-08
**Status:** PLANNED — not yet executed (plan only; no code until approved)
**Order note:** Phase 8 is being done **before** Phase 7 (email/Calendar). Confirmed: Phase 8 has **zero dependency** on Phase 7.

---

## 0. Current state (what exists today)

| Area | Route | View | Backing data |
|------|-------|------|--------------|
| Reports | `admin.reports.index` — **closure** returning view | `admin/reports/index.blade.php` — **hardcoded `@php` arrays** | none |
| Audit Log | `admin.audit-log.index` — **closure** | `admin/audit-log/index.blade.php` — **hardcoded `@php` array** | `audit_logs` table (real rows exist) |
| Settings | `admin.settings.index` — **closure** | `admin/settings/index.blade.php` — form with **hardcoded values**, posts `PUT` to a route that doesn't accept it yet | `lab_settings` table |

**Already in place (reuse, don't rebuild):**
- `LabSetting::get($key, $default)` and `LabSetting::set($key, $value)` — done.
- `AuditLog` model (morphTo `auditable`, `belongsTo user`, array casts on old/new values).
- Audit rows are **already written** for: approve / reject / complete (`AdminRequestController`), computer status (`AdminComputerController`), user create/update (`AdminUserController`), team create/update (`AdminTeamController`), and auto-reject (`BookingService`). Each does an inline `AuditLog::create([...])` with the same 8-field boilerplate.
- Settings values are **already read** by the booking engine: `BookingService::checkConflict()` reads `buffer_minutes`; `CalendarController::validateBusinessRules()` reads `operating_days`, `operating_start`, `operating_end`, `max_session_hours`. → So "wire settings into logic" (old 8.10/8.11) is **already done**; Phase 8 only needs the **admin UI to edit** them.

**Gaps to close:**
- Booking **creation** (`CalendarController::store`) and **cancellation** (`BookingController::cancel`) currently write **no** audit log. Phase 8 adds `booking.submitted` and `booking.cancelled`.
- The Settings page's **Google Calendar** section is Phase 7 — excluded here.

---

## 1. Decisions — CONFIRMED (2026-06-08)

| # | Decision | Chosen |
|---|----------|--------|
| D1 | Charts vs CSS bars | **CSS bars** — feed real data into the existing CSS-bar visuals. No Chart.js. |
| D2 | Audit writing approach | **`AuditLogService`** central helper. Migrate the 5 existing call sites + add the 2 missing actions. |
| D3 | Exports | **No exports.** Drop the whole exports sub-feature. **Delete the export buttons** instead (Reports: "Ekspor Excel" + "Ekspor PDF"; Audit Log: "Ekspor Log"). No new Composer packages, no export routes. |
| D4 | `audit_logs.auditable_type` nullable migration | **Yes** — add it so model-less actions (`settings.updated`) can be logged. |

> Settings scope: edit only the keys that drive real behavior (see §3); `google_calendar_id` stays out (Phase 7).

---

## 2. Cross-cutting: `AuditLogService` (shared infrastructure — build first)

**New file:** `app/Services/AuditLogService.php`

```php
class AuditLogService
{
    public static function record(
        string $action,
        ?Model $auditable = null,
        array $old = [],
        array $new = [],
    ): void {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id'   => $auditable?->getKey(),
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
```
- **Migration note:** `audit_logs.auditable_type` is currently `NOT NULL`. If we want to log actions with no model (e.g. `settings.updated`, `user.login`), either (a) make `auditable_type`/`auditable_id` nullable via a small migration, or (b) always pass the related model. **Recommended:** small migration to make them nullable (cleaner for `settings.updated`).
- **Migrate existing call sites** (5 controllers + `BookingService`) to `AuditLogService::record(...)` — pure refactor, identical rows, much less boilerplate.
- **Add new logging:**
  - `CalendarController::store()` → `AuditLogService::record('booking.submitted', $booking, [], ['status' => 'submitted'])`
  - `BookingController::cancel()` → `AuditLogService::record('booking.cancelled', $booking, ['status'=>$old], ['status'=>'cancelled'])`
  - `AdminSettingsController::update()` → `AuditLogService::record('settings.updated', null, $oldValues, $newValues)`

---

## 3. Sub-feature A — Settings (smallest; do first)

**Goal:** Make the Settings page read/write the real `lab_settings` table.

**New controller:** `app/Http/Controllers/Admin/AdminSettingsController.php`
- `index()` — load all setting values via `LabSetting::get()`, pass to view.
- `update(Request $request)` — validate, `LabSetting::set()` each key, audit-log, redirect back with success.

**Editable keys (drive real behavior):**
| Key | Field | Validation |
|-----|-------|-----------|
| `lab_name` | text | required, string, max:255 |
| `admin_email` | email | required, email |
| `operating_start` | select HH:00 | required, date_format:H:i |
| `operating_end` | select HH:00 | required, after `operating_start` |
| `operating_days` | checkboxes → CSV `"1,2,3,4,5,6"` | required, array, each in 0–6 |
| `max_session_hours` | number | required, integer, 1–8 |
| `buffer_minutes` | number | required, integer, 0–60 |

> `operating_days` is stored as a CSV string (existing format `"1,2,3,4,5,6"`). Controller joins the checkbox array; view splits it back to pre-check boxes.

**Routes (replace the settings closure):**
```php
Route::get('/settings',  [AdminSettingsController::class, 'index'])->name('settings.index');
Route::put('/settings',  [AdminSettingsController::class, 'update'])->name('settings.update');
```

**View edits (`admin/settings/index.blade.php`):**
- Form `action` → `route('admin.settings.update')` (keep `@method('PUT')`).
- Replace every hardcoded `value="..."` / `selected` / `checked` with the loaded setting.
- **Remove the Google Calendar `<x-section>`** (Phase 7). Optionally keep `session_lifetime` if you want it editable (currently not on the form).
- Show `@if($errors->any())` + success toast (layout already renders `session('success')`).

**Acceptance:**
- Page shows the live DB values.
- Changing operating hours / buffer / max duration and saving → values persist, and a **new booking immediately respects them** (since the engine already reads `lab_settings`).
- An audit row `settings.updated` is written.

---

## 4. Sub-feature B — Audit Log

**Goal:** Replace the hardcoded list with real `audit_logs`, filterable + paginated.

**New controller:** `app/Http/Controllers/Admin/AdminAuditLogController.php`
- `index(Request $request)`:
  - Query `AuditLog::with('user')->latest('created_at')`.
  - Filters: `action` (exact), `user_id` (exact), `date_from`/`date_to` (whereDate range), `q` (search action + auditable id / booking code).
  - `->paginate(20)->withQueryString()`.
  - Pass distinct `actions` and `users` lists for the filter dropdowns.

**Presentation mapping** (move out of the view into a small helper/array): action → human description + dot color, e.g.
```
booking.submitted → "Permintaan baru dikirim"  · amber
booking.approved  → "Reservasi disetujui"      · green
booking.rejected  → "Reservasi ditolak"        · red
booking.completed → "Reservasi diselesaikan"   · teal
booking.cancelled → "Reservasi dibatalkan"     · grey
booking.auto_rejected → "Ditolak otomatis"     · red/grey
computer.status_changed → "Status unit diubah" · grey
user.created / user.updated → …                · review
team.created / team.updated → …                · review
settings.updated → "Pengaturan diperbarui"     · grey
```
- "Target" column: resolve `auditable_type`+`auditable_id` → booking `booking_code` / computer `label` / user `name`. Use eager `with('auditable')` or a light per-row lookup (small page size).

**Routes (replace closure):**
```php
Route::get('/audit-log', [AdminAuditLogController::class, 'index'])->name('audit-log.index');
```

**View edits (`admin/audit-log/index.blade.php`):**
- Delete the hardcoded `$logs` array; loop `$logs` paginator instead.
- Wire the filter `<select>`/date/search inputs to query-string (`name=` + preserve selected).
- Real pagination (`{{ $logs->links() }}` or the existing prev/next styled to the paginator).
- Show real "Menampilkan X dari Y".

**Acceptance:**
- Real history shows (newest first); approving a booking in another tab then refreshing shows the new row.
- Filters by action/user/date/search work and survive pagination.

---

## 5. Sub-feature C — Reports

**Goal:** Replace hardcoded analytics with real aggregates over `bookings` / `booking_logbooks` / `booking_computers` / `users` / `computers`, for a selected date range.

**New service:** `app/Services/ReportService.php` — pure aggregation, range-aware (`$from`, `$to`):
| Metric | Source |
|--------|--------|
| Total reservasi | `bookings` count in range (status ∈ submitted…completed, configurable) |
| Tingkat pemakaian (%) | approved+completed booked-hours ÷ available-hours (operating hours × operating days × 9 units, or room-level — define) |
| Pengguna aktif | distinct `user_id` with a booking in range |
| Rata-rata durasi | avg(end−start) over counted bookings |
| Pemakaian per minggu (jam) | sum hours grouped by ISO week |
| Breakdown kategori | `booking_logbooks.category` counts + % |
| Pengguna paling aktif | group by `user_id` → count + total hours, top 5 |
| Pemakaian per unit komputer | `booking_computers` join → booked hours per PC ÷ available; PCs in maintenance flagged |

**New controller:** `app/Http/Controllers/Admin/AdminReportController.php`
- `index(Request $request)` — parse range (`period` preset or explicit `from`/`to`; default current month), call `ReportService`, pass results to view.

**Routes (replace closure):**
```php
Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
```

**View edits (`admin/reports/index.blade.php`):**
- Replace each hardcoded `@php` array with the service output (same CSS-bar markup — D1).
- Wire the date-range preset buttons + date inputs to query-string so the page reloads with the chosen range (server-rendered; no Chart.js).

**Acceptance:**
- Numbers match a hand SQL check for a known range.
- Changing the range re-computes all sections.
- Empty range → clean "no data" states (no divide-by-zero).

---

## 6. Sub-feature D — Remove export buttons (per D3: no exports)

No export functionality is built. Instead, **delete the now-purposeless export buttons** so the UI matches reality:
- `admin/reports/index.blade.php` → remove the **"Ekspor Excel"** and **"Ekspor PDF"** buttons from the `<x-slot:actions>` header (the `<x-slot:actions>` can be removed entirely if empty).
- `admin/audit-log/index.blade.php` → remove the **"Ekspor Log"** button from its `<x-slot:actions>`.

No export routes, controllers, packages, or `App\Exports` classes.

---

## 7. New files & routes summary

**New files**
| File | Purpose |
|------|---------|
| `app/Services/AuditLogService.php` | central audit writer |
| `app/Services/ReportService.php` | report aggregations |
| `app/Http/Controllers/Admin/AdminSettingsController.php` | settings read/write |
| `app/Http/Controllers/Admin/AdminAuditLogController.php` | audit log index |
| `app/Http/Controllers/Admin/AdminReportController.php` | reports |
| `database/migrations/xxxx_make_audit_auditable_nullable.php` | allow model-less audit rows (settings.updated) |

**Edited files**
| File | Change |
|------|--------|
| `routes/web.php` | 3 closures → controllers; add `settings.update` (no export routes) |
| `admin/settings/index.blade.php` | real values, update action, drop GCal section |
| `admin/audit-log/index.blade.php` | real paginated/filtered data; **remove "Ekspor Log" button** |
| `admin/reports/index.blade.php` | real aggregated data + range wiring; **remove "Ekspor Excel/PDF" buttons** |
| 5 controllers + `BookingService` | swap inline `AuditLog::create` → `AuditLogService::record` |
| `CalendarController::store` | add `booking.submitted` audit |
| `BookingController::cancel` | add `booking.cancelled` audit |

**No DB schema change** except the optional `audit_logs` nullable migration.

---

## 8. Suggested execution order (incremental, each verifiable)

1. **AuditLogService** (+ nullable migration) → migrate existing call sites (no behavior change) + add booking.submitted / booking.cancelled. *Verify: audit rows still written identically; 2 new actions appear.*
2. **Settings** (controller + routes + view). *Verify: edit → persist → new booking respects new hours/buffer; `settings.updated` logged.*
3. **Audit Log** (controller + routes + view; remove "Ekspor Log" button). *Verify: real rows, filters, pagination.*
4. **Reports** (service + controller + routes + view; remove "Ekspor Excel/PDF" buttons). *Verify: numbers vs SQL; range switching.*
5. Commit per sub-feature.

---

## 9. Out of scope (Phase 7 / later)
- Email notifications, Google Calendar sync, `google_calendar_id` setting.
- Chart.js (unless D1 changed).
- New audit actions for `user.login` (would need a login hook) — optional add-on.
