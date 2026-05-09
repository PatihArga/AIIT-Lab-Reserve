# UKRIDA Lab Reserve — Project Handover Document

**Date:** 9 May 2026  
**Project:** UKRIDA Lab Reserve — Computer Laboratory Booking System  
**Stack:** Laravel 12 · MySQL · Blade · Alpine.js · Tailwind CSS v3  
**Environment:** XAMPP (Windows) · PHP 8.x · Node.js  
**App URL:** `http://localhost/UKRIDA_LabReserve/public`

---

## 1. Project Summary

A web-based computer laboratory reservation system for Universitas Kristen Krida Wacana (UKRIDA). The system manages booking requests for 1 lab room containing 9 computer units (PC-01 to PC-09).

### Core Capabilities (Planned)
- Role-based access: Admin, Lecturer, Team (student group)
- 3 booking types: Computers Only, Full Room + Computers, Room Only
- Multi-step booking form with conflict detection
- Admin approval workflow (approve / reject with reason)
- Google Calendar sync (create / delete events on approve / reject)
- Email notifications (submission, approval, rejection)
- Booking logbook (editable only when Approved or Completed)
- Usage reports and analytics
- Full audit log

### Roles

| Role | Access |
|------|--------|
| **Admin** | Full system — manages accounts, approves/rejects, views all data |
| **Lecturer** | Submits bookings under own name, views own history + logbook |
| **Team** | Student group entity; PIC (a lecturer) is assigned; logs in as the team |

### Login Flow (Users)
1. Enter study program email → system detects program from email domain
2. Select name from dropdown → enter password → authenticated

### Login Flow (Admin)
- Separate page at `/admin/login` — direct email + password (single step)

---

## 2. Credentials (Seeded)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@ukrida.ac.id` | `Admin@123` |
| Test Lecturer | (seeded via `TestLecturerSeeder`) | (check seeder file) |

---

## 3. Key Files & Folders

| Path | Purpose |
|------|---------|
| `PLAN-UI.md` | Master spec — full DB schema, routes, component list, feature modules |
| `routes/web.php` | All application routes |
| `routes/auth.php` | Auth routes (login, admin login, logout) |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | 2-step + admin login logic |
| `app/Http/Middleware/AdminOnly.php` | Guards all `/admin/*` routes |
| `app/Http/Middleware/ActiveUserOnly.php` | Blocks deactivated accounts |
| `resources/css/app.css` | Tailwind config + all CSS custom properties / design tokens |
| `resources/views/components/` | All reusable Blade components |
| `resources/views/layouts/app.blade.php` | Main app shell (sidebar + topbar) |
| `resources/views/layouts/` + `auth-layout.blade.php` component | Auth shell |
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
| `npm run build` passing | ✅ Done | 81 kB CSS + 88 kB JS |

---

### PHASE 1 — Database & Models
**Status: LARGELY COMPLETE — migrations and models built, minor gap noted**

#### Migrations

| Migration | Status | Notes |
|-----------|--------|-------|
| `create_users_table` (customized Breeze) | ✅ Done | Includes `role`, `study_program_id`, `is_active`, `last_login_at` |
| `create_study_programs_table` | ⚠️ Check | `StudyProgram` model exists; verify migration file exists separately |
| `create_teams_table` | ✅ Done | |
| `create_team_members_table` | ⚠️ Check | `TeamMember` model exists; verify migration file |
| `create_computers_table` | ✅ Done | |
| `create_bookings_table` | ✅ Done | |
| `create_booking_computers_table` (pivot) | ⚠️ Check | Relationship works via `belongsToMany`; verify pivot migration |
| `create_booking_logbooks_table` | ✅ Done | |
| `create_audit_logs_table` | ✅ Done | |
| `create_lab_settings_table` | ✅ Done | |

#### Models

| Model | Status | Notes |
|-------|--------|-------|
| `User` | ✅ Done | Relationships to StudyProgram, Team, Booking |
| `StudyProgram` | ✅ Done | |
| `Team` | ✅ Done | |
| `TeamMember` | ✅ Done | |
| `Computer` | ✅ Done | |
| `Booking` | ✅ Done | `isEditable()`, `isCancellable()` helper methods |
| `BookingLogbook` | ✅ Done | |
| `AuditLog` | ✅ Done | |
| `LabSetting` | ✅ Done | |

#### Seeders

| Seeder | Status | Notes |
|--------|--------|-------|
| `StudyProgramSeeder` | ✅ Done | |
| `AdminUserSeeder` | ✅ Done | `admin@ukrida.ac.id` / `Admin@123` |
| `ComputerSeeder` | ✅ Done | PC-01 through PC-09 |
| `LabSettingsSeeder` | ✅ Done | Default operating hours, buffer minutes, etc. |
| `TestLecturerSeeder` | ✅ Done | Test lecturer account for development |
| `DatabaseSeeder` | ✅ Done | Calls all seeders in dependency order |

---

### PHASE 2 — Authentication
**Status: COMPLETE**

| Task | Status | Notes |
|------|--------|-------|
| Step 1 login (email → study program detect) | ✅ Done | `detectStudyProgram()` in controller |
| Step 2 login (name dropdown + password) | ✅ Done | `selectUser()` + `authenticate()` |
| Admin login (direct email + password) | ✅ Done | `adminAuthenticate()` — separate page at `/admin/login` |
| Non-admin blocked from admin portal | ✅ Done | `adminAuthenticate()` checks `role === 'admin'` |
| `AdminOnly` middleware | ✅ Done | Applied to all `/admin/*` routes |
| `ActiveUserOnly` middleware | ✅ Done | Blocks `is_active = false` accounts |
| No public `/register` route | ✅ Done | Removed from Breeze |
| Rate limiting | ✅ Done | 5 attempts per IP; separate limiter for admin login |
| Role-based redirect after login | ✅ Done | Admin → `/admin/dashboard`, others → `/dashboard` |
| Password reset | ❌ Not done | Admin resets password via edit user form (UI exists, no backend) |
| Session timeout (configurable) | ❌ Not done | Planned via `lab_settings` |

---

### PHASE 3 — Layouts & Design System
**Status: COMPLETE**

| Task | Status | Notes |
|------|--------|-------|
| `auth-layout` component | ✅ Done | Split-panel: brand left, form right |
| `app.blade.php` layout | ✅ Done | 256px sidebar + topbar + content area |
| Design tokens (CSS custom props) | ✅ Done | `ink-*`, `mark-*`, `status-*`, `rule`, `bg-*` |
| `app-sidebar` component | ✅ Done | Role-aware nav; user links + admin links |
| `page-header` component | ✅ Done | Eyebrow + title + actions slot |
| `section` component | ✅ Done | Labelled content card |
| `badge` component | ✅ Done | Maps status string to CSS class |
| `stat-hero` component | ✅ Done | Large metric display |
| `step-indicator` component | ✅ Done | Multi-step progress bar |
| `computer-grid` component | ✅ Done | 9-unit grid; `selectable` + `name` props |
| `modal` component | ✅ Done | Alpine.js modal wrapper |
| `empty-state` component | ✅ Done | |
| `dropdown-menu` / `dropdown-item` | ✅ Done | |
| `user-menu` component | ✅ Done | Avatar + logout in topbar |
| Form components (`form/input`, `form/select`, `form/textarea`, `form/toggle`, `form/radio-card`) | ✅ Done | |
| CSS utility classes (`btn-mark`, `btn-ghost`, `btn-lg`, `form-field`, `form-label`, `mono-data`, `mono-code`, etc.) | ✅ Done | Defined in `app.css` |
| `CalendarWidget` component (FullCalendar.js) | ❌ Not done | Planned for Phase 5+ |
| `Toast` component | ❌ Not done | Planned |
| `ConfirmModal` | ❌ Not done | Planned |

---

### PHASE 4 — Static Frontend (All Pages)
**Status: COMPLETE — all 21 pages built with dummy data**

#### Auth Pages

| Page | Route | File | Status |
|------|-------|------|--------|
| A1 — User Login Step 1 | `/login` | `auth/login.blade.php` | ✅ Done |
| A2 — User Login Step 2 | `/login/select` | `auth/select-user.blade.php` | ✅ Done |
| A3 — Admin Login | `/admin/login` | `auth/admin-login.blade.php` | ✅ Done |

#### User Pages

| Page | Route | File | Status |
|------|-------|------|--------|
| U1 — User Dashboard | `/dashboard` | `dashboard.blade.php` | ✅ Done |
| U2 — Booking: Select Type | `/booking/create` | `booking/create.blade.php` | ✅ Done |
| U3 — Booking: Schedule | `/booking/create/schedule` | `booking/schedule.blade.php` | ✅ Done |
| U4 — Booking: Information | `/booking/create/logbook` | `booking/logbook.blade.php` | ✅ Done |
| U5 — Booking: Review & Submit | `/booking/create/review` | `booking/review.blade.php` | ✅ Done |
| U6 — Booking History | `/booking/history` | `booking/history.blade.php` | ✅ Done |
| U7 — Booking Detail | `/booking/{id}` | `booking/show.blade.php` | ✅ Done |

**Booking Flow Step Order:** Select Type → Schedule → Information → Review & Submit

**Logbook access rule (enforced in view):**
- Pending / Rejected / Cancelled → logbook locked (read-only message shown)
- Approved / Completed + empty logbook → "Isi Logbook" toggle button appears
- Approved / Completed + filled logbook → display + edit button

#### Admin Pages

| Page | Route | File | Status |
|------|-------|------|--------|
| AD1 — Admin Dashboard | `/admin/dashboard` | `admin/dashboard.blade.php` | ✅ Done |
| AD2 — Requests List | `/admin/requests` | `admin/requests/index.blade.php` | ✅ Done |
| AD3 — Request Detail | `/admin/requests/{id}` | `admin/requests/show.blade.php` | ✅ Done |
| AD4 — Computer Management | `/admin/computers` | `admin/computers/index.blade.php` | ✅ Done |
| AD5 — Users List | `/admin/users` | `admin/users/index.blade.php` | ✅ Done |
| AD6 — Create Lecturer | `/admin/users/create` | `admin/users/create.blade.php` | ✅ Done |
| AD7 — Edit User | `/admin/users/{id}/edit` | `admin/users/edit.blade.php` | ✅ Done |
| AD8 — Create Team | `/admin/teams/create` | `admin/teams/create.blade.php` | ✅ Done |
| AD9 — Reports | `/admin/reports` | `admin/reports/index.blade.php` | ✅ Done |
| AD10 — Audit Log | `/admin/audit-log` | `admin/audit-log/index.blade.php` | ✅ Done |
| AD11 — Settings | `/admin/settings` | `admin/settings/index.blade.php` | ✅ Done |

> All pages use hardcoded dummy data. No real database reads yet.

---

### PHASE 5 — Backend Wiring (Booking Flow)
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| `BookingService` class | ❌ Not done | Core service for conflict detection + booking creation |
| Conflict detection (race condition safe with `lockForUpdate`) | ❌ Not done | Critical — must use DB transactions |
| POST `/booking` — store booking | ❌ Not done | Form validation + DB write |
| `BookingRequest` form request class | ❌ Not done | Validates all 4 steps merged |
| Booking code auto-generation (`LAB-XXXX`) | ❌ Not done | |
| GET `/booking/{id}` — real data | ❌ Not done | Currently shows hardcoded dummy |
| GET `/booking/history` — real data | ❌ Not done | Currently shows hardcoded dummy |
| PUT `/booking/{id}/logbook` — save logbook | ❌ Not done | |
| POST `/booking/{id}/cancel` — cancel booking | ❌ Not done | |
| Real availability check API (AJAX) | ❌ Not done | `checkAvailability()` in schedule.blade.php is currently a stub |
| Session carry-through across booking steps | ❌ Not done | Query params currently used in static flow |

---

### PHASE 6 — Admin Approval Workflow
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| POST `/admin/requests/{id}/approve` | ❌ Not done | Sets status + triggers Calendar + email |
| POST `/admin/requests/{id}/reject` | ❌ Not done | Requires reason text |
| POST `/admin/requests/{id}/mark-completed` | ❌ Not done | |
| Admin requests list — real data | ❌ Not done | Currently hardcoded dummy |
| Admin request detail — real data | ❌ Not done | |
| Computer status toggle (online ↔ maintenance) | ❌ Not done | |
| User create / edit / deactivate backend | ❌ Not done | |
| Team create backend | ❌ Not done | |
| Password reset (admin-initiated) | ❌ Not done | |

---

### PHASE 7 — Email Notifications + Google Calendar
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| Mail classes (`BookingSubmittedMail`, `BookingApprovedMail`, `BookingRejectedMail`) | ❌ Not done | |
| Branded email templates | ❌ Not done | |
| Google Calendar service account setup | ❌ Not done | Requires `.json` credentials file |
| `GoogleCalendarService` | ❌ Not done | Create / update / delete events |
| Queue jobs for Calendar + email | ❌ Not done | Non-blocking background dispatch |
| `.env` keys for Calendar + SMTP | ❌ Not done | `GOOGLE_CALENDAR_ID`, `MAIL_*` |

---

### PHASE 8 — Reports, Audit Log, Settings Backend
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| Reports — real DB aggregates | ❌ Not done | Usage rate, category breakdown, active users, per-PC usage |
| PDF export (`barryvdh/laravel-dompdf`) | ❌ Not done | Package not yet installed |
| Excel export (`Maatwebsite/Laravel-Excel`) | ❌ Not done | Package not yet installed |
| Audit log — real data + write on every action | ❌ Not done | Model exists; write logic not wired |
| Settings form — read/write `lab_settings` table | ❌ Not done | |
| `LabSetting::get('key')` helper | ❌ Not done | |

---

### PHASE 9 — Testing & Security Hardening
**Status: NOT STARTED**

| Task | Status | Notes |
|------|--------|-------|
| Feature tests (booking creation, conflict, approval) | ❌ Not done | |
| Unit tests (`BookingService`, conflict logic) | ❌ Not done | |
| CSRF protection | ✅ Partial | Laravel default; POST forms use `@csrf` |
| Authorization policies (Booking, User) | ❌ Not done | No `Policy` classes yet |
| Input sanitization / XSS prevention | ✅ Partial | Blade auto-escapes; needs review on raw output |
| SQL injection prevention | ✅ Partial | Eloquent ORM used throughout |
| Rate limiting (login) | ✅ Done | 5 attempts per IP |
| `is_active` check on every login | ✅ Done | `ActiveUserOnly` middleware |

---

## 5. What to Build Next (Recommended Order)

1. **Phase 5 first** — Backend wiring for the booking flow is the highest priority. Without it the app can't accept any real bookings.
   - Start with `BookingService` + conflict detection
   - Then wire the 4-step form to actually store a `Booking` record
   - Then wire `/booking/history` and `/booking/{id}` to real data

2. **Phase 6** — Admin approval after booking submission works, since it depends on real bookings existing.

3. **Phase 7** — Email + Google Calendar can be done in parallel with Phase 6, but depends on a working `.env` SMTP + Calendar credentials.

4. **Phase 8** — Reports and settings backend.

5. **Phase 9** — Testing + policies last, once real logic is in place.

---

## 6. Known Issues / Watch Points

| Issue | Location | Notes |
|-------|----------|-------|
| Booking flow uses GET params across steps | `schedule.blade.php` → `logbook.blade.php` → `review.blade.php` | When backend is wired, switch to session or a draft `Booking` record |
| `review.blade.php` shows hardcoded dummy data | `booking/review.blade.php` | Must be replaced with actual session/draft values |
| `Booking::isEditable()` checks `approved` + `under_review` | `app/Models/Booking.php:44` | But the view only allows logbook edit for `approved` + `completed` — reconcile these |
| `study_programs` / `team_members` / `booking_computers` migrations | `database/migrations/` | Verify these tables exist; model relationships assume them |
| No `Toast` / confirmation modal | Components missing | Approve / reject actions will need these before Phase 6 UI is functional |
| `checkAvailability()` JS is a stub | `booking/schedule.blade.php:120` | Returns "available" unconditionally — must be replaced with a real AJAX call |
