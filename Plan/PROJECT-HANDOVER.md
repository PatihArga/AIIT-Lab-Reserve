# UKRIDA Lab Reserve — Project Handover Document

**Last Updated:** 26 May 2026  
**Project:** UKRIDA Lab Reserve — Computer Laboratory Booking System  
**Stack:** Laravel 12 · MySQL · Blade · Alpine.js · Tailwind CSS v3  
**Environment:** XAMPP (Windows) · PHP 8.x · Node.js  
**App URL:** `http://localhost/UKRIDA_LabReserve/public`

---

## 1. Project Summary

A web-based computer laboratory reservation system for Universitas Kristen Krida Wacana (UKRIDA). The system manages booking requests for 1 lab room containing 9 computer units (PC-01 to PC-09).

### Core Capabilities
- Role-based access: Admin, Lecturer, Team (student group)
- 3 booking types: Computers Only (`computers_only`), Full Room + Computers (`full_room`), Room Only (`room_only`)
- Room-only has two sharing modes: `exclusive` (blocks room entirely) and `shared` (allows simultaneous computer-only bookings)
- Multi-step booking form with real conflict detection (race-condition safe via `lockForUpdate`)
- Admin approval workflow (approve / reject with reason / mark-completed)
- Auto-reject conflicting pending requests when one booking is approved
- Booking logbook (editable when Approved or Completed)
- Full audit log written on every state-changing action
- Google Calendar sync (planned — Phase 7)
- Email notifications (planned — Phase 7)
- Usage reports and analytics (planned — Phase 8)

### Roles

| Role | Access |
|------|--------|
| **Admin** | Full system — manages accounts, approves/rejects, views all data |
| **Lecturer** | Submits bookings under own name, views own history + logbook |
| **Team** | Student group entity; PIC (a lecturer) is assigned; logs in as the team |

### Current Login Flow (Users)
1. Enter the **study program's Gmail address** (e.g. `ti.ukrida@gmail.com`) → exact match against `study_programs.email`
2. Select name from dropdown (admin accounts excluded) → enter password → authenticated

### Login Flow (Admin)
- Separate page at `/admin/login` — enter the admin's **personal Gmail** + password
- Backend looks up `users.gmail` where `role = 'admin'`, then verifies the password with `Hash::check`
- Admin accounts are **not tied to any study program** (`users.study_program_id` is `NULL` for admins)

---

## 2. Credentials (Seeded)

| Role | Login portal | Login identifier | Password |
|------|--------------|------------------|----------|
| Admin | `/admin/login` | `admin.ukrida@gmail.com` (admin's `users.gmail`) | `Admin@123` |
| Test Lecturer | `/login` | Step 1: `ti.ukrida@gmail.com` (program Gmail) → Step 2: pick `Dr. Budi Santoso` | `Test@123` |
| Test Team | `/login` | Step 1: `ti.ukrida@gmail.com` → Step 2: pick `Tim Alpha` | `Test@123` |

> Note: the admin's `users.email` (`admin@ukrida.ac.id`) is no longer used for login — only `users.gmail` is checked on `/admin/login`. The `email` column remains as the unique account identifier.

---

## 3. Key Files & Folders

| Path | Purpose |
|------|---------|
| `Plan/PLAN-UI.md` | Master spec — full DB schema, routes, component list, feature modules |
| `Plan/AuthRework/PLAN-LOGIN-DROPDOWN-AND-ADMIN-GMAIL.md` | **Executed 2026-05-26** — auth rework: study-program Gmail lookup + admin Gmail login |
| `routes/web.php` | All application routes |
| `routes/auth.php` | Auth routes (login, admin login, logout) |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | 2-step user login (Gmail lookup) + admin Gmail login |
| `app/Services/BookingService.php` | Core booking logic: conflict detection, creation, auto-reject |
| `app/Http/Controllers/BookingController.php` | All user-facing booking routes (dashboard, create flow, history, detail, cancel) |
| `app/Http/Controllers/Api/AvailabilityController.php` | AJAX availability check endpoint (used by schedule page) |
| `app/Http/Controllers/Admin/AdminRequestController.php` | Admin approval/reject/complete workflow |
| `app/Http/Controllers/Admin/AdminUserController.php` | User create / edit (lecturers only) |
| `app/Http/Controllers/Admin/AdminTeamController.php` | Team create / edit |
| `app/Http/Controllers/Admin/AdminComputerController.php` | Computer status toggle |
| `app/Http/Middleware/AdminOnly.php` | Guards all `/admin/*` routes |
| `app/Http/Middleware/ActiveUserOnly.php` | Blocks deactivated accounts |
| `resources/css/app.css` | Tailwind config + all CSS custom properties / design tokens |
| `resources/views/components/` | All reusable Blade components |
| `resources/views/layouts/app.blade.php` | Main app shell (sidebar + topbar) |
| `database/seeders/` | Seeder files for admin, computers, study programs, lab settings |

---

## 4. Phase-by-Phase Status

---

### PHASE 0 — Project Initialization
**Status: COMPLETE**

| Task | Status | Notes |
|------|--------|-------|
| Laravel project created | ✅ Done | |
| `.env` configured | ✅ Done | `DB_DATABASE=UKRIDA_LabReserve`, timezone, locale |
| Tailwind CSS v3 installed | ✅ Done | Custom design tokens in `app.css` |
| Alpine.js installed | ✅ Done | |
| Livewire 3 installed | ✅ Done | (available but not yet used in views) |
| Laravel Breeze installed | ✅ Done | Registration route removed |
| `npm run build` passing | ✅ Done | |

---

### PHASE 1 — Database & Models
**Status: COMPLETE**

#### Migrations

| Migration | Status | Notes |
|-----------|--------|-------|
| `create_users_table` | ✅ Done | Includes `role`, `study_program_id`, `is_active`, `last_login_at` |
| `create_study_programs_table` | ✅ Done | `email_domain` → renamed to `email` by 2026_05_26_000001 migration; stores full Gmail addresses |
| `rename_email_domain_to_email_in_study_programs` (2026_05_26_000001) | ✅ Done | Renames column; preserves data + unique index |
| `add_gmail_to_users` (2026_05_26_000002) | ✅ Done | Nullable + unique `gmail` column; populated for admin accounts only |
| `create_teams_table` | ✅ Done | |
| `create_team_members_table` | ✅ Done | |
| `create_computers_table` | ✅ Done | |
| `create_bookings_table` | ✅ Done | Includes `booking_type`, `room_sharing`, `booking_code`, `status`, `reviewed_by`, `reviewed_at`, `admin_notes` |
| `create_booking_computers_table` (pivot) | ✅ Done | |
| `create_booking_logbooks_table` | ✅ Done | |
| `create_audit_logs_table` | ✅ Done | |
| `create_lab_settings_table` | ✅ Done | |

#### Models

| Model | Status | Notes |
|-------|--------|-------|
| `User` | ✅ Done | Relationships to StudyProgram, Team, Booking; `$fillable` includes `gmail` (used by admin login) |
| `StudyProgram` | ✅ Done | `$fillable` updated to `['name', 'email', 'is_active']`; `email` stores program Gmail |
| `Team` | ✅ Done | |
| `TeamMember` | ✅ Done | |
| `Computer` | ✅ Done | |
| `Booking` | ✅ Done | `isEditable()`, `isCancellable()` helper methods; `booking_type` + `room_sharing` fields |
| `BookingLogbook` | ✅ Done | |
| `AuditLog` | ✅ Done | |
| `LabSetting` | ✅ Done | |

#### Seeders

| Seeder | Status | Notes |
|--------|--------|-------|
| `StudyProgramSeeder` | ✅ Done | Seeds 4 programs (TI, SI, TE, TI) with Gmail addresses; Administrator program intentionally **not seeded** |
| `AdminUserSeeder` | ✅ Done | `admin@ukrida.ac.id` / `Admin@123`; `gmail = admin.ukrida@gmail.com`; `study_program_id = NULL` (admins don't belong to a program) |
| `ComputerSeeder` | ✅ Done | PC-01 through PC-09 |
| `LabSettingsSeeder` | ✅ Done | Default operating hours, buffer minutes, etc. |
| `TestLecturerSeeder` | ✅ Done | Test lecturer + test team account; both linked to Teknik Informatika |
| `DatabaseSeeder` | ✅ Done | Calls all seeders in dependency order |

---

### PHASE 2 — Authentication
**Status: COMPLETE (Gmail rework done 2026-05-26)**

| Task | Status | Notes |
|------|--------|-------|
| Step 1 login (study program Gmail lookup) | ✅ Done | `detectStudyProgram()` — exact match on `study_programs.email` |
| Step 2 login (name dropdown + password) | ✅ Done | `selectUser()` excludes admin accounts; `authenticate()` unchanged |
| Admin login (Gmail + password) | ✅ Done | `adminAuthenticate()` — `User::where('gmail', ?)->where('role', 'admin')` + `Hash::check` |
| Non-admin blocked from admin portal | ✅ Done | `adminAuthenticate()` filters by `role = 'admin'` at the query level |
| Admin not selectable in user login | ✅ Done | Administrator study program deleted; `selectUser()` also filters `role != admin` (defense in depth) |
| `AdminOnly` middleware | ✅ Done | Applied to all `/admin/*` routes |
| `ActiveUserOnly` middleware | ✅ Done | Blocks `is_active = false` accounts |
| No public `/register` route | ✅ Done | Removed from Breeze |
| Rate limiting | ✅ Done | 5 attempts per IP; `throttleKeyByEmail()` reads `gmail` first, falls back to `email` |
| Role-based redirect after login | ✅ Done | Admin → `/admin/dashboard`, others → `/dashboard` |
| Password reset | ❌ Not done | Admin resets password via edit user form (UI exists, no backend) |

---

### PHASE 3 — Layouts & Design System
**Status: COMPLETE**

| Task | Status |
|------|--------|
| `auth-layout` component | ✅ Done |
| `app.blade.php` layout | ✅ Done |
| Design tokens (CSS custom props) | ✅ Done |
| `app-sidebar` component | ✅ Done |
| `page-header`, `section`, `badge`, `stat-hero`, `step-indicator` components | ✅ Done |
| `computer-grid` component | ✅ Done |
| `modal` component | ✅ Done |
| `empty-state`, `dropdown-menu`, `user-menu` components | ✅ Done |
| Form components (`form/input`, `form/select`, `form/textarea`, `form/toggle`, `form/radio-card`) | ✅ Done |
| CSS utility classes (`btn-mark`, `btn-ghost`, `form-field`, `form-label`, `mono-data`, etc.) | ✅ Done |
| `Toast` / `ConfirmModal` components | ❌ Not done |

---

### PHASE 4 — Static Frontend (All Pages)
**Status: COMPLETE — all pages built and wired to real data (Phase 5 complete)**

#### Auth Pages

| Page | Route | Status |
|------|-------|--------|
| A1 — User Login Step 1 | `/login` | ✅ Done — enter program Gmail (exact match on `study_programs.email`) |
| A2 — User Login Step 2 | `/login/select` | ✅ Done — name dropdown filtered to exclude admins, plus password |
| A3 — Admin Login | `/admin/login` | ✅ Done — Gmail-based lookup on `users.gmail` (admin role only) |

#### User Pages

| Page | Route | Status |
|------|-------|--------|
| U1 — User Dashboard | `/dashboard` | ✅ Done + real data |
| U2 — Booking: Select Type | `/booking/create` | ✅ Done |
| U3 — Booking: Schedule | `/booking/schedule` | ✅ Done + real AJAX availability |
| U4 — Booking: Information | `/booking/logbook` | ✅ Done |
| U5 — Booking: Review & Submit | `/booking/review` | ✅ Done |
| U6 — Booking History | `/booking/history` | ✅ Done + real data |
| U7 — Booking Detail | `/booking/{id}` | ✅ Done + real data |

**Booking Flow Step Order:** Select Type → Schedule → Information → Review & Submit  
**Session key:** `booking_draft` carries state across steps; cleared with `?reset=1` on entry.

#### Admin Pages

| Page | Route | Status |
|------|-------|--------|
| AD1 — Admin Dashboard | `/admin/dashboard` | ✅ Done + real stats |
| AD2 — Requests List | `/admin/requests` | ✅ Done + real data |
| AD3 — Request Detail | `/admin/requests/{id}` | ✅ Done + real data |
| AD4 — Computer Management | `/admin/computers` | ✅ Done |
| AD5 — Users List | `/admin/users` | ✅ Done + real data |
| AD6 — Create Lecturer | `/admin/users/create` | ✅ Done |
| AD7 — Edit User | `/admin/users/{id}/edit` | ✅ Done |
| AD8 — Create Team | `/admin/teams/create` | ✅ Done |
| AD9 — Reports | `/admin/reports` | ⚠️ UI done; data hardcoded (Phase 8) |
| AD10 — Audit Log | `/admin/audit-log` | ⚠️ UI done; data hardcoded (Phase 8) |
| AD11 — Settings | `/admin/settings` | ⚠️ UI done; read/write not wired (Phase 8) |

---

### PHASE 5 — Backend Wiring (Booking Flow)
**Status: COMPLETE**

| Task | Status | Notes |
|------|--------|-------|
| `BookingService` class | ✅ Done | `app/Services/BookingService.php` |
| Conflict detection (`lockForUpdate`, race-condition safe) | ✅ Done | `checkConflict()` — `approvedOnly` param controls display vs. hard-block |
| `autoRejectConflicting()` | ✅ Done | Auto-rejects other pending requests when one is approved |
| `BookingStoreRequest` form request | ✅ Done | Full validation for all booking fields |
| `BookingConflictException` | ✅ Done | Thrown by service; caught in controllers |
| POST `/booking` — store booking | ✅ Done | Writes `Booking` + pivot + audit log in DB transaction |
| Booking code auto-generation (`LAB-XXXX`) | ✅ Done | |
| GET `/booking/{id}` — real data | ✅ Done | |
| GET `/booking/history` — real data | ✅ Done | Paginated, filterable |
| POST `/booking/{id}/cancel` — cancel booking | ✅ Done | |
| PUT `/booking/{id}/logbook` — save logbook | ✅ Done | Allowed only when `approved` or `completed` |
| AJAX availability check (`GET /api/availability`) | ✅ Done | Returns `available`, `pending`, `message` |
| Session draft (`booking_draft`) across steps | ✅ Done | `?reset=1` clears stale draft |
| `shared_room_active` draft key | ✅ Done | Passed from calendar via `?room_shared=1`; disables non-computer booking types on schedule page |

---

### PHASE 6 — Admin Approval Workflow
**Status: COMPLETE**

| Task | Status | Notes |
|------|--------|-------|
| POST `/admin/requests/{id}/approve` | ✅ Done | Live conflict check before approve; `autoRejectConflicting` runs in same transaction |
| POST `/admin/requests/{id}/reject` | ✅ Done | Requires `admin_notes` reason |
| POST `/admin/requests/{id}/complete` | ✅ Done | Transitions `approved` → `completed` |
| Auto-transition `submitted` → `under_review` on detail open | ✅ Done | |
| Admin requests list — real data + filter + search | ✅ Done | |
| Admin request detail — real data + live conflict indicator | ✅ Done | |
| Computer status toggle (online ↔ maintenance) | ✅ Done | `AdminComputerController` |
| User create / edit (lecturers) backend | ✅ Done | `AdminUserController` with audit log |
| Team create / edit backend | ✅ Done | `AdminTeamController` |
| Audit log written on every state change | ✅ Done | `AuditLog::create()` in every approve/reject/complete/create/update |
| Password reset (admin-initiated) | ✅ Done | Admin can set new password from edit user form |

---

### PHASE 7 — Email Notifications + Google Calendar
**Status: NOT STARTED**

| Task | Status |
|------|--------|
| Mail classes (`BookingSubmittedMail`, `BookingApprovedMail`, `BookingRejectedMail`) | ❌ Not done |
| Branded email templates | ❌ Not done |
| Google Calendar service account setup | ❌ Not done |
| `GoogleCalendarService` | ❌ Not done |
| Queue jobs for Calendar + email | ❌ Not done |
| `.env` keys for Calendar + SMTP | ❌ Not done |

---

### PHASE 8 — Reports, Audit Log, Settings Backend
**Status: NOT STARTED**

| Task | Status |
|------|--------|
| Reports — real DB aggregates | ❌ Not done |
| PDF export (`barryvdh/laravel-dompdf`) | ❌ Not done |
| Excel export (`Maatwebsite/Laravel-Excel`) | ❌ Not done |
| Audit log — real data + UI | ❌ Not done |
| Settings form — read/write `lab_settings` table | ❌ Not done |
| `LabSetting::get('key')` helper | ❌ Not done |

---

### PHASE 9 — Testing & Security Hardening
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| Feature tests (booking creation, conflict, approval) | ❌ Not done | |
| Unit tests (`BookingService`, conflict logic) | ❌ Not done | |
| CSRF protection | ✅ Partial | Laravel default; all POST forms use `@csrf` |
| Authorization policies (Booking, User) | ❌ Not done | No `Policy` classes yet |
| Input sanitization / XSS prevention | ✅ Partial | Blade auto-escapes; needs review on raw output |
| SQL injection prevention | ✅ Partial | Eloquent ORM used throughout |
| Rate limiting (login) | ✅ Done | 5 attempts per IP |
| `is_active` check on every login | ✅ Done | `ActiveUserOnly` middleware |

---

## 5. Edge Cases Fixed (Session May 2026)

These bugs were identified through manual testing and fixed during active development. Each has a corresponding plan document in `Plan/`.

### EC-A — Shared-room bookings invisible on dashboard calendar
- **Problem:** `room_only + shared` bookings were approved and in the DB but showed no visual state on the calendar, allowing users to attempt incompatible booking types (e.g. `full_room`) without any warning.
- **Fix:** Added `$sharedRoomBlocks` computation in `BookingController::dashboard()` (grouped by day/hour across all active statuses). Dashboard JS renders a new teal "Berbagi" state and passes `sharedRoomModal` flag into the slot modal.
- **Plan:** `Plan/EdgeCase-RoomSharingConflict/PLAN-ROOM-SHARING-CONFLICT-FIX.md`

### EC-B — `hasPending` misleading for incompatible booking types
- **Problem:** `AvailabilityController::check()` reported "available, you have a pending request in this slot" even when the pending request was type-incompatible (e.g. pending `full_room` while requesting `computers_only`). The single `checkConflict` call with `approvedOnly: false` caught only hard conflicts, not type-specific pending collisions.
- **Fix:** Two `checkConflict` calls in a single `DB::transaction`: one `approvedOnly: true` (hard blocks), one `approvedOnly: false` (also checks pending). Both must be false for `available: true`. `hasPending` is only true when there is genuinely no conflict of any kind.
- **Plan:** Same as EC-A above.

### EC-C — Schedule page didn't enforce shared-room type restrictions (EC1)
- **Problem:** Clicking "Buat Reservasi" from the shared-room slot modal navigated to the schedule page with full type options, even though the modal had disabled non-computer types.
- **Fix:** Dashboard JS sets `?room_shared=1` in the URL when navigating from a shared-room slot. `BookingController::showSchedule()` reads this and sets `shared_room_active: true` in `booking_draft`. The schedule view reads this key and disables `full_room` and `room_only` radio cards.
- **Plan:** `Plan/EdgeCase-RoomSharingConflict/PLAN-BOOKING-TYPE-RESTRICTION-FIX.md`

### EC-D — User's own "Saya" slot bypassed shared-room restrictions (EC2)
- **Problem:** When a user had their own `room_only + shared` booking, clicking that slot opened the modal without the shared-room restriction banner, because `sharedRoom` was gated behind `!isMine`.
- **Fix:** Split `sharedRoom` into `sharedRoomModal` (drives modal logic — not gated by `!isMine`) and `sharedRoom` (drives visual-only teal state — still gated). The modal always receives the correct shared-room flag regardless of ownership.
- **Plan:** Same as EC-C above.

### EC-E — Calendar showing "Penuh" instead of "Saya" for own bookings
- **Problem:** For a user's own `full_room` or `room_only + exclusive` approved booking, the calendar showed the red "Penuh" state because `hardBlocked` check ran before `isMine` in the class assignment.
- **Fix:** Inverted visual priority in `renderTimeSlots()` — `isMine` now takes precedence over `hardBlocked` in both the CSS class and the status label string. Own bookings always display blue "Saya".

### EC-F — Stale `shared_room_active` carrying over to fresh bookings
- **Problem:** If a user navigated from a shared-room slot to the schedule page, then used the browser back button and started a new booking from the dashboard header button, the `booking_draft` still had `shared_room_active: true`, wrongly disabling non-computer types.
- **Fix:** All "Buat Reservasi" entry points (dashboard header button, booking history header, app sidebar link) now use `route('booking.schedule', ['reset' => 1])`. The schedule controller checks `?reset=1` and clears the `booking_draft` session before proceeding.

### EC-G — Auth rework: study program Gmail lookup + admin Gmail login (2026-05-26)
- **Problem 1:** The old Step 1 login extracted the `@domain` suffix from a user's institutional email and looked it up in `study_programs.email_domain`. Any user whose email was not from a pre-registered institutional domain (e.g. Gmail users) was locked out.
- **Problem 2:** The admin account appeared in the Step 2 dropdown because it shared the "Administrator" study program with no role filter — leaked admin existence + extra attack surface for admin credentials.
- **Problem 3:** Supervisor wanted Gmail to be a real first-class identifier, but specifically on **admin user records** (not on study programs).
- **Fix (database):**
  - Migration `2026_05_26_000001_rename_email_domain_to_email_in_study_programs` — renames `email_domain` → `email`, preserves data and unique index.
  - Migration `2026_05_26_000002_add_gmail_to_users` — adds nullable + unique `gmail` column after `users.email`.
  - `study_programs` rows reseeded with full Gmail addresses (e.g. `ti.ukrida@gmail.com`); "Administrator" program removed entirely.
  - Admin user updated to `gmail = admin.ukrida@gmail.com`, `study_program_id = NULL` (admins are no longer tied to any program).
- **Fix (controller):**
  - `detectStudyProgram()` — removed domain extraction; does an exact match `StudyProgram::where('email', $email)`. Adds `where('role', '!=', 'admin')` to the `$hasUsers` check.
  - `selectUser()` — added `where('role', '!=', 'admin')` filter so admin accounts never appear in the Step 2 name list.
  - `adminAuthenticate()` — no longer uses `Auth::attempt`. Looks up `User::where('gmail', ?)->where('role', 'admin')`, verifies the password with `Hash::check`, then `Auth::login($admin)`.
  - `throttleKeyByEmail()` — reads `gmail` first, falls back to `email`, so admin rate-limit keys are correct.
- **Fix (views):**
  - `auth/login.blade.php` — label/step → "Gmail Program Studi"; placeholder → `nama.prodi@gmail.com`. Field `name="email"` retained so `LoginEmailRequest` validation is unchanged.
  - `auth/select-user.blade.php` — completed-step label → "Gmail Program Studi"; back link text → "← Gunakan gmail lain".
  - `auth/admin-login.blade.php` — field renamed `email` → `gmail`; label "Gmail Admin"; placeholder `nama@gmail.com`.
- **Fix (seeders):**
  - `StudyProgramSeeder` — Administrator entry removed; programs keyed by `name`.
  - `AdminUserSeeder` — no longer looks up program; sets `study_program_id = null` and `gmail = admin.ukrida@gmail.com`.
  - `TestLecturerSeeder` — looks up program by `name = 'Teknik Informatika'`.
- **Plan:** `Plan/AuthRework/PLAN-LOGIN-DROPDOWN-AND-ADMIN-GMAIL.md` (executed)
- **Migration & rollback:** during execution, an earlier failed rollback attempt left 5 stale duplicate study program rows (IDs 6-10) that had no FK references; those were deleted manually. Fresh installs run cleanly via `php artisan migrate && php artisan db:seed --class=StudyProgramSeeder && php artisan db:seed --class=AdminUserSeeder`.

---

## 6. What to Build Next (Recommended Order)

1. **Phase 7 — Email + Google Calendar** — users expect notifications after submission, approval, and rejection. Requires SMTP `.env` keys and a Google service account JSON file. Now that the auth rework is done, the admin's `users.gmail` is the natural sender identity for system mails.

2. **Phase 8 — Reports, Audit Log UI, Settings backend** — admin pages currently show hardcoded data.

3. **Phase 9 — Testing + Policies** — add `Booking` and `User` policies to prevent cross-user access; add feature tests for the conflict detection and approval flow. Auth tests should cover the three Gmail rejection paths: wrong program Gmail, admin Gmail at `/login`, lecturer Gmail at `/admin/login`.

4. **UI polish (low priority)** — Toast / ConfirmModal components for approve/reject UX.

---

## 7. Known Issues / Watch Points

| Issue | Location | Notes |
|-------|----------|-------|
| Reports, Audit Log UI, Settings pages show hardcoded data | `admin/reports`, `admin/audit-log`, `admin/settings` | Phase 8 work. |
| No email notifications on booking state changes | — | Phase 7 work. Approve/reject currently silent. |
| No Google Calendar integration | — | Phase 7 work. |
| No authorization Policy classes | `app/Policies/` (missing) | A user can theoretically GET any `/booking/{id}` URL if they guess the ID. Gate with a `BookingPolicy` in Phase 9. |
| No Toast / ConfirmModal components | `resources/views/components/` | Approve/reject actions use direct form submit; a confirmation dialog would improve UX. |
| `is_active` check in `ActiveUserOnly` middleware | `app/Http/Middleware/ActiveUserOnly.php` | Works for existing sessions, but a user deactivated mid-session stays logged in until next request. Consider adding a session guard. |
| Admin account creation requires manual seeder/SQL | `AdminUserSeeder` + manual | `AdminUserController::store()` hard-codes `role => 'lecturer'`. New admins can only be created via seeder or direct DB insert; the admin UI does not expose an admin-creation form by design. |
| Admin's `users.email` (`admin@ukrida.ac.id`) is unused by login | `users` table | Only `users.gmail` is checked at `/admin/login`. `email` remains as the unique account identifier (FK target, audit log reference) but plays no auth role for admins. |
