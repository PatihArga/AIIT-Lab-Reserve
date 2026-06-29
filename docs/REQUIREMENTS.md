# AIIT Lab Reserve — Software Requirements Specification

**Project:** UKRIDA / AIIT Lab Reserve System  
**Version:** 1.0  
**Date:** 2026-06-23  
**Language:** English  
**Status:** As-built (reflects the current implemented system)

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Stakeholders & User Roles](#2-stakeholders--user-roles)
3. [Functional Requirements](#3-functional-requirements)
   - 3.1 Authentication
   - 3.2 Lab Calendar & Booking Submission
   - 3.3 Booking Management (User)
   - 3.4 Logbook
   - 3.5 Admin — Request Approval
   - 3.6 Admin — Computer Management
   - 3.7 Admin — User & Team Management
   - 3.8 Admin — Reports
   - 3.9 Admin — Audit Log
   - 3.10 Admin — Lab Settings
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [Business Rules](#5-business-rules)
6. [Constraints & Assumptions](#6-constraints--assumptions)

---

## 1. Introduction

### 1.1 Purpose

This document specifies the functional and non-functional requirements for the AIIT Lab Reserve system — a web application for managing computer laboratory reservations at UKRIDA university. It serves as the reference for development, testing, and future maintenance.

### 1.2 Scope

The system enables academic staff (lecturers and student teams) to book the computer laboratory and allows the lab administrator to review, approve, and manage those bookings. It covers:

- Two-step authentication tied to study programs
- Calendar-based booking with real-time conflict detection
- Admin approval workflow
- Post-session logbook for academic accountability
- Computer inventory management
- Audit logging and reporting

### 1.3 Definitions

| Term | Meaning |
|---|---|
| Booking | A reservation of lab resources (room, computers, or both) for a specific date and time |
| Study Program | An academic department (e.g., Teknik Informatika) that authenticates users via a shared Gmail account |
| Logbook | A post-session record attached to an approved booking describing academic work done |
| Buffer | A mandatory gap between consecutive bookings (default 15 minutes) |
| PIC Lecturer | The supervising lecturer responsible for a student team |

---

## 2. Stakeholders & User Roles

### 2.1 Roles

| Role | Description |
|---|---|
| **Admin** | Laboratory administrator. Manages users, approves bookings, configures settings. Has a direct email + password login; not tied to any study program. |
| **Lecturer** | Academic staff member. Can create bookings and edit logbooks for their approved sessions. |
| **Team** | A student group account supervised by a PIC Lecturer. Has the same booking capabilities as a Lecturer. |
| **Guest** | Unauthenticated visitor. Can only see the login page. |

### 2.2 Permissions Matrix

| Feature | Admin | Lecturer | Team |
|---|---|---|---|
| View own dashboard | ✓ | ✓ | ✓ |
| View lab calendar | ✓ | ✓ | ✓ |
| Create a booking | — | ✓ | ✓ |
| Cancel own booking | — | ✓ | ✓ |
| Edit own logbook | — | ✓ | ✓ |
| View own booking history | — | ✓ | ✓ |
| Review & approve/reject bookings | ✓ | — | — |
| Mark booking as completed | ✓ | — | — |
| Manage computer inventory | ✓ | — | — |
| Manage user accounts | ✓ | — | — |
| Manage team accounts | ✓ | — | — |
| View reports | ✓ | — | — |
| View audit log | ✓ | — | — |
| Edit lab settings | ✓ | — | — |

---

## 3. Functional Requirements

### 3.1 Authentication

#### FR-AUTH-01: Two-Step Lecturer / Team Login

The system shall implement a two-step login flow for Lecturers and Teams:

- **Step 1 — Email Verification:** The user enters the study program's shared Gmail address. The system validates it against registered study programs and verifies the shared password. On success, the system stores the resolved study program in the session and redirects to Step 2.
- **Step 2 — User Selection:** The user selects their personal account from a dropdown of active users belonging to the verified study program, then confirms. The system logs the user in without requiring an additional personal password.

#### FR-AUTH-02: Admin Login

The system shall provide a separate admin login page (`/admin/login`) where the administrator authenticates using their personal email address and password directly (no study program step).

#### FR-AUTH-03: Rate Limiting

Both login paths shall enforce a rate limit of 5 failed attempts per IP + email combination per 60-second window to protect against brute-force attacks.

#### FR-AUTH-04: Active Account Guard

The system shall reject login and terminate active sessions for any user whose `is_active` flag has been set to false, redirecting them to the login page with an appropriate message.

#### FR-AUTH-05: Logout

The system shall provide a logout action that invalidates the user's session and CSRF token and redirects to the login page.

#### FR-AUTH-06: Last Login Tracking

The system shall record the timestamp of each successful login on the user's account (`last_login_at`).

---

### 3.2 Lab Calendar & Booking Submission

#### FR-CAL-01: Week-View Calendar

The system shall display an interactive week-view calendar showing a 5-week window (one past week + current week + three future weeks). Each approved and pending booking shall be rendered as a color-coded event block.

#### FR-CAL-02: Event Color Coding

Calendar events shall be visually differentiated by booking type:

| Type | Color |
|---|---|
| Computers only | Indigo |
| Full room + computers | Violet |
| Room only — exclusive | Teal |
| Room only — shared | Amber |

#### FR-CAL-03: Booking Submission from Calendar

The system shall allow a Lecturer or Team to submit a new booking via an inline popover form directly on the calendar, without leaving the page. The form shall capture:

- Booking type (`computers_only`, `full_room`, `room_only`)
- Date, start time, and end time
- Room sharing preference (exclusive or shared) — for `room_only` only
- Computer selection — for `computers_only` and `full_room`
- Session purpose / initial logbook entry (minimum 3 characters)
- Logbook category (research, academic project, practicum, thesis, other)

#### FR-CAL-04: Real-Time Availability Check

The system shall perform an AJAX availability check when the user finishes entering the time slot or selects a booking type. The response shall distinguish between:

- **Blocked:** a hard conflict exists with an already-approved booking.
- **Pending:** no hard conflict, but overlapping pending bookings exist that may be auto-rejected if the user's booking is later approved first.
- **Available:** no conflicts of any kind.

#### FR-CAL-05: Computer Availability Endpoint

The system shall expose an AJAX endpoint that returns, for a given date/time slot, the availability status of each computer unit (available, pending, or blocked), taking the 15-minute buffer and room-locking rules into account.

#### FR-CAL-06: Conflict Prevention on Submit

The system shall re-verify the absence of conflicts inside a database transaction when the user submits a booking. If a conflict is detected between the AJAX check and the final submission, the system shall reject the request and return an error message without creating the booking.

#### FR-CAL-07: Booking Code Generation

Each successfully created booking shall be assigned a unique, sequential booking code in the format `LAB-NNNN` (e.g., `LAB-0001`).

#### FR-CAL-08: Initial Status

Newly submitted bookings shall be assigned the status `submitted` and a `submitted_at` timestamp.

---

### 3.3 Booking Management (User)

#### FR-BKG-01: Booking Detail View

The system shall allow a user to view the full detail of any booking they own, including: booking code, type, date, times, selected computers, current status, logbook content, admin notes (if rejected), and the name of the reviewing admin.

#### FR-BKG-02: Booking History

The system shall provide a paginated list (15 per page) of the authenticated user's past and present bookings, filterable by status and date, and searchable by booking code.

#### FR-BKG-03: Dashboard Summary

Each authenticated user's dashboard shall display:

- Count of upcoming (submitted, under review, approved) bookings
- Total bookings in the current calendar month
- Count of pending (unreviewed) requests
- The oldest pending booking code (if any)
- Total approved hours booked
- Visual status of all computer units in the lab

#### FR-BKG-04: Booking Cancellation

The system shall allow a user to cancel their own booking if its current status is `submitted`, `under_review`, or `approved`. Once cancelled, the status changes to `cancelled` and the action is audit-logged.

---

### 3.4 Logbook

#### FR-LOG-01: Logbook Availability

The system shall allow a user to view and edit the logbook for any of their bookings whose status is `approved` or `completed`.

#### FR-LOG-02: Mandatory Logbook Fields

The logbook shall require:

- **Category:** one of `penelitian` (research), `project_akademik` (academic project), `praktikum` (practicum), `tugas_akhir` (thesis), or `lainnya` (other).
- **Checkpoint / Progress Notes:** a text field between 10 and 2,000 characters describing the work done.

#### FR-LOG-03: Optional Logbook Fields

The logbook shall optionally capture:

- Related course name
- Supervisor name
- Whether the allocated time was sufficient
- List of special software used or installed
- Whether internet access is needed
- Whether software installation is required
- List of external devices brought (flash drives, etc.)
- Priority level (normal / urgent) and reason
- Session target / goals

#### FR-LOG-04: Installation Dependency

If the user checks "needs installation," the `special_software` field becomes required. If the user unchecks it before saving, the `special_software` value shall be cleared.

#### FR-LOG-05: Logbook Audit Trail

Every logbook edit shall be recorded in the audit log with a field-level before/after diff, capturing only the fields that changed.

---

### 3.5 Admin — Request Approval

#### FR-REQ-01: Request Queue

The admin shall have a paginated list (20 per page) of all booking requests, filterable by status (pending, approved, rejected, cancelled, completed), date, and searchable by booking code or user name.

#### FR-REQ-02: Auto-Transition to Under Review

When an admin opens a booking detail page whose status is `submitted`, the system shall automatically transition it to `under_review` to signal that the request is being actively evaluated.

#### FR-REQ-03: Live Conflict Check on Detail

The booking detail view for admins shall show a live conflict check result indicating whether approving this booking would conflict with any already-approved booking.

#### FR-REQ-04: Approve Booking

The admin shall be able to approve a booking in `submitted` or `under_review` status. Before approving, the system shall:

1. Re-run a conflict check inside a transaction.
2. If a conflict is detected, reject the approval and surface an error.
3. If clear, set status to `approved`, record `reviewed_by` and `reviewed_at`, and automatically reject any pending bookings that now conflict with the newly approved booking (see FR-REQ-06).

#### FR-REQ-05: Past-Date Soft Guard

If the admin attempts to approve a booking whose date is in the past, the system shall display a confirmation warning before proceeding. The approval shall only complete if the admin explicitly confirms.

#### FR-REQ-06: Auto-Rejection of Conflicting Pending Bookings

When a booking is approved, the system shall automatically reject any overlapping pending (submitted or under review) bookings whose type is incompatible with the newly approved one. Each auto-rejection shall be audit-logged with the action `booking.auto_rejected` and a reason referencing the approved booking code.

#### FR-REQ-07: Reject Booking

The admin shall be able to reject a booking in `submitted` or `under_review` status, optionally providing a rejection reason that is stored in `admin_notes` and visible to the requester.

#### FR-REQ-08: Mark as Completed

The admin shall be able to mark an `approved` booking as `completed` once the session has ended.

---

### 3.6 Admin — Computer Management

#### FR-CMP-01: Inventory View

The admin shall see a list of all computer units ordered by unit number, with counts of online, under-maintenance, and offline units.

#### FR-CMP-02: Status Update

The admin shall be able to change a computer's operational status. The following transitions are permitted:

| From | To (allowed) |
|---|---|
| `online` | `online`, `maintenance`, `offline` |
| `maintenance` | `maintenance`, `online` |
| `offline` | `offline`, `online` |

All other transitions shall be rejected with a validation error.

#### FR-CMP-03: Specs Note

The admin shall be able to update a free-text hardware/software specifications note for each computer unit without necessarily changing its status.

#### FR-CMP-04: Status Audit Logging

A status change shall be recorded in the audit log with before and after status values. Updating only the specs note does not produce an audit entry.

---

### 3.7 Admin — User & Team Management

#### FR-USR-01: User List

The admin shall view a paginated list (20 per page) of all non-admin users (lecturers and teams), filterable by role and study program, and searchable by name or email. Each entry shall show the user's total booking count.

#### FR-USR-02: Create Lecturer Account

The admin shall be able to create a new lecturer account by providing: full name, email address, study program, and whether the account is active.

#### FR-USR-03: Edit Lecturer Account

The admin shall be able to edit any non-admin user's name, email, study program, active status, and optionally reset their password.

#### FR-USR-04: Create Team Account

The admin shall be able to create a team account by providing: team name, login email, study program, PIC (supervising) lecturer, an optional description, and a list of team members (each with a name and student ID number).

#### FR-USR-05: Edit Team Account

The admin shall be able to edit all fields of a team account, including replacing the full member list.

#### FR-USR-06: User Audit Logging

Creating or updating a user or team account shall be recorded in the audit log. Password changes shall be represented as `[changed]` rather than storing the actual value.

---

### 3.8 Admin — Reports

#### FR-RPT-01: Date Range Selection

The admin shall be able to generate a booking report for a custom date range or using preset periods: current week, current month, current quarter, and current year.

#### FR-RPT-02: Report Metrics

The report shall present booking statistics for the selected period, including totals by status and booking type.

---

### 3.9 Admin — Audit Log

#### FR-AUD-01: Audit Timeline View

The admin shall have a searchable, filterable, paginated (20 per page) view of the complete audit log showing: the actor, the action, the affected entity, before/after values, IP address, and timestamp.

#### FR-AUD-02: Audit Log Filters

The audit log shall support filtering by: action type, actor (user), date range, and free-text search on the action or associated booking code.

#### FR-AUD-03: Audit Log Stats

The audit log view shall display: total log entries matching the current filter, entries logged today matching the current filter, total processed booking actions (all time), and count of distinct active users (all time).

#### FR-AUD-04: Immutability

Audit log entries shall be append-only. No update or delete operations shall be permitted on existing entries.

#### FR-AUD-05: Audit Actions Covered

The system shall audit-log the following actions:

| Action | Trigger |
|---|---|
| `booking.submitted` | User submits a new booking |
| `booking.approved` | Admin approves a booking |
| `booking.rejected` | Admin rejects a booking |
| `booking.auto_rejected` | System auto-rejects a conflicting pending booking |
| `booking.cancelled` | User cancels their booking |
| `booking.completed` | Admin marks a booking as completed |
| `logbook.updated` | User edits their logbook (field-level diff) |
| `computer.status_changed` | Admin changes a computer's status |
| `user.created` | Admin creates a user account |
| `user.updated` | Admin edits a user account |
| `team.created` | Admin creates a team account |
| `team.updated` | Admin edits a team account |
| `settings.updated` | Admin saves updated lab settings |

---

### 3.10 Admin — Lab Settings

#### FR-SET-01: Configurable Parameters

The admin shall be able to view and update the following lab configuration parameters:

| Setting | Type | Description |
|---|---|---|
| Lab name | String | Display name of the laboratory |
| Admin email | Email | Contact / notification address |
| Operating start time | Time (HH:MM) | Earliest bookable time of day |
| Operating end time | Time (HH:MM) | Latest bookable time of day |
| Operating days | Weekdays (multi-select) | Days the lab is open (Monday–Sunday) |
| Max session hours | Integer (1–8) | Maximum duration of a single booking |
| Buffer minutes | Integer (0–60) | Mandatory gap between consecutive bookings |

#### FR-SET-02: Settings Validation

The system shall validate that operating end time is after operating start time, at least one operating day is selected, and max session hours and buffer minutes are within their allowed ranges.

#### FR-SET-03: Settings Audit Logging

Saving settings shall produce an audit log entry recording only the keys whose values actually changed, with before and after values.

---

## 4. Non-Functional Requirements

### 4.1 Performance

| ID | Requirement |
|---|---|
| NFR-PERF-01 | Page response time for dashboard, calendar, and list views shall be under 2 seconds on a local network connection under typical load (≤ 20 concurrent users). |
| NFR-PERF-02 | The AJAX availability check (FR-CAL-04) shall respond within 500 ms under typical load. |
| NFR-PERF-03 | Database queries for calendar event rendering shall use indexed columns (`date`, `status`, `start_time`, `end_time`) to avoid full table scans. |
| NFR-PERF-04 | Paginated list views (bookings, users, audit log) shall use `LIMIT/OFFSET` pagination and shall not load unbounded result sets into memory. |

### 4.2 Security

| ID | Requirement |
|---|---|
| NFR-SEC-01 | All user-facing routes shall be protected by Laravel's CSRF middleware. |
| NFR-SEC-02 | Passwords (user and study program) shall be stored as bcrypt hashes (cost ≥ 12). |
| NFR-SEC-03 | Authentication shall be rate-limited (5 attempts per 60 seconds per IP + email) to mitigate brute-force attacks. |
| NFR-SEC-04 | A user shall not be able to view, modify, or cancel bookings belonging to another user (ownership enforced at the controller level with HTTP 403 on violation). |
| NFR-SEC-05 | The admin section (`/admin/*`) shall be inaccessible to non-admin authenticated users (HTTP 403). |
| NFR-SEC-06 | Deactivated user accounts shall be denied access immediately, even mid-session (enforced by the `ActiveUserOnly` middleware). |
| NFR-SEC-07 | Audit log entries shall be immutable once written; no controller or service shall expose an update or delete path for audit records. |
| NFR-SEC-08 | Password values shall never appear in audit log entries; they shall be masked as `[changed]`. |
| NFR-SEC-09 | SQL injection shall be prevented through the use of Laravel's Eloquent ORM and query builder (parameterised queries only; no raw string interpolation into queries). |
| NFR-SEC-10 | All output rendered in Blade templates shall be HTML-escaped by default (`{{ }}`) to prevent XSS; unescaped output (`{!! !!}`) shall only be used for trusted, system-generated content. |

### 4.3 Reliability & Data Integrity

| ID | Requirement |
|---|---|
| NFR-REL-01 | Booking creation and admin approval shall be executed inside database transactions with row-level locking (`lockForUpdate`) to prevent race conditions and double-bookings. |
| NFR-REL-02 | Database migrations shall be idempotent when run on an already-migrated schema (standard Laravel `migrate` behaviour). |
| NFR-REL-03 | Seeders shall use `updateOrCreate` to be safe to re-run without duplicating data or resetting user-created content. |
| NFR-REL-04 | Foreign key constraints shall be enforced at the database level for all inter-table relationships. |
| NFR-REL-05 | The booking code sequence shall be collision-free; generation shall be performed inside a transaction that locks the bookings table to prevent two simultaneous submissions from receiving the same code. |

### 4.4 Usability

| ID | Requirement |
|---|---|
| NFR-USE-01 | The user interface shall be in Indonesian (Bahasa Indonesia) for all labels, messages, validation errors, and enum display values. |
| NFR-USE-02 | All datetimes shall be displayed in the Asia/Jakarta timezone. |
| NFR-USE-03 | Form validation errors shall be displayed inline next to the relevant field, not only at the top of the page. |
| NFR-USE-04 | Success and error feedback shall be displayed via flash messages visible immediately after a redirect. |
| NFR-USE-05 | The calendar shall visually distinguish bookings made by the current user (via an `is_mine` flag) from those made by others. |
| NFR-USE-06 | The admin's booking detail view shall display a live conflict indicator so the admin does not need to perform a manual check before approving. |

### 4.5 Maintainability

| ID | Requirement |
|---|---|
| NFR-MNT-01 | Business logic (conflict detection, booking creation, auto-rejection) shall be encapsulated in service classes (`BookingService`, `AuditLogService`) rather than placed directly in controllers. |
| NFR-MNT-02 | Lab configuration values shall be stored in the `lab_settings` table and read via `LabSetting::get()` so they can be changed at runtime without code deployments. |
| NFR-MNT-03 | The application shall use Laravel Form Requests for input validation to keep controller methods focused on orchestration. |
| NFR-MNT-04 | Frontend assets (CSS, JS) shall be compiled via Vite; raw Tailwind classes must be available in the build output before deployment (no JIT-only classes at runtime). |

### 4.6 Portability & Deployment

| ID | Requirement |
|---|---|
| NFR-DEP-01 | The application shall be runnable via Docker Compose using a single command (`docker compose up -d --build`) without any prior manual configuration steps beyond providing a `.env.docker` file. |
| NFR-DEP-02 | The Docker stack shall include: a PHP 8.2 application container (Laravel + Node 20 for asset builds), a MariaDB 11 database container, and a phpMyAdmin container for database inspection. |
| NFR-DEP-03 | The containerised application shall be accessible to other devices on the same local network via the host machine's LAN IP address and the configured port (default 8000). |
| NFR-DEP-04 | The application shall also run on a standard XAMPP stack (PHP 8.2+, MySQL/MariaDB) for local development without Docker. |
| NFR-DEP-05 | Database migrations and seeding shall run automatically on container start-up; seeding shall only execute on a fresh (empty) database to avoid overwriting existing data. |

### 4.7 Scalability

| ID | Requirement |
|---|---|
| NFR-SCL-01 | The system is designed for a single-lab, single-institution deployment with an expected concurrent user base of under 50 users. It is not required to support horizontal scaling or multi-tenancy. |
| NFR-SCL-02 | Session data, cache, and queued jobs shall use the database driver by default, eliminating the need for Redis or Memcached in the base deployment. |

---

## 5. Business Rules

### 5.1 Booking Type Semantics

| Type | Room Locked | Computers Required | Sharing |
|---|---|---|---|
| `computers_only` | No | Yes (≥ 1) | N/A |
| `full_room` | Yes (exclusive) | Optional | Exclusive |
| `room_only` | Yes | None | Exclusive or Shared |

### 5.2 Conflict Rules

Two bookings in the same date conflict when their time windows overlap (with the 15-minute buffer applied to the end time of each) **and** their types are incompatible:

| Existing \ New | `computers_only` | `full_room` | `room_only exclusive` | `room_only shared` |
|---|---|---|---|---|
| `computers_only` | Conflict only if same PC IDs | Conflict | Conflict | No conflict |
| `full_room` | Conflict | Conflict | Conflict | Conflict |
| `room_only exclusive` | Conflict | Conflict | Conflict | Conflict |
| `room_only shared` | No conflict | Conflict | Conflict | No conflict |

### 5.3 Booking Status Lifecycle

```
[submitted] ──► [under_review] ──► [approved] ──► [completed]
                      │                 │
                      ▼                 ▼
                  [rejected]        [cancelled]
```

- `submitted` → `under_review`: automatic when admin opens the detail page.
- `under_review` → `approved` / `rejected`: admin action.
- `approved` → `completed`: admin action.
- Any status in {submitted, under_review, approved} → `cancelled`: user action.
- `approved` → `rejected` (as `auto_rejected`): system action on conflicting pending bookings.

### 5.4 Operating Constraints

- Bookings may not be placed on dates in the past (users) or outside configured operating days.
- Booking start and end times must fall within the configured operating hours (default 08:00–22:00).
- A single booking may not exceed the configured maximum session duration (default 4 hours).
- A 15-minute buffer (configurable) separates consecutive bookings in the same slot.

### 5.5 Computer Availability

- Only computers with status `online` may be selected for a booking.
- Computers under `maintenance` or `offline` are excluded from availability queries.

### 5.6 Logbook Editability

- A logbook may only be created or edited when the booking status is `approved` or `completed`.
- If `needs_installation` is false, the `special_software` field is cleared on save.

---

## 6. Constraints & Assumptions

| ID | Constraint / Assumption |
|---|---|
| CON-01 | The system manages a single laboratory with a fixed set of 9 computer units. Adding or removing units requires an admin action (not a code change). |
| CON-02 | The system does not send email notifications in its base configuration; mail is logged to `storage/logs`. Email delivery is deferred to a future phase. |
| CON-03 | Google Calendar integration (`google_event_id` field) is planned but not implemented. |
| CON-04 | The application requires PHP 8.2 or higher and a MySQL / MariaDB compatible database. |
| CON-05 | There is no self-registration flow; all user accounts (lecturer and team) are created by the admin. |
| CON-06 | Study program shared credentials (Gmail address + password) are managed directly in the database by the admin; there is no self-service password reset for study programs. |
| CON-07 | The admin account cannot create bookings on behalf of users. Booking creation is restricted to Lecturer and Team roles. |
| CON-08 | The system does not implement role granularity beyond the three roles (admin, lecturer, team); all lecturers have identical permissions. |
