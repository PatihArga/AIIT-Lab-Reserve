# Auth Rework Plan — Review

**Reviewed:** 26 May 2026  
**Plan Under Review:** [`PLAN-EMAIL-LOGIN-AND-ADMIN-SEPARATION.md`](file:///c:/xampp/htdocs/UKRIDA_LabReserve/Plan/AuthRework/PLAN-EMAIL-LOGIN-AND-ADMIN-SEPARATION.md)  
**Project:** UKRIDA Lab Reserve  
**Reviewer:** AI Agent (Antigravity)

---

## 1. Context — What the Supervisor Asked For

The supervisor raised two specific complaints about the current authentication system:

1. **The `email_domain`-based login is too rigid.** Users must have an institutional email matching a registered domain (e.g., `@ti.ukrida.ac.id`). The supervisor wants users to log in with **any email address** — including Gmail — so the hard coupling between study programs and email domains must be removed.

2. **Admin accounts are accessible through the regular user login.** Because the admin account (`admin@ukrida.ac.id`) is tied to the "Administrator" study program, it appears in the user dropdown at `/login/select`. This leaks the admin's existence and creates a second attack surface for admin credentials.

---

## 2. What the Plan File Contains (Summary)

The plan at [`PLAN-EMAIL-LOGIN-AND-ADMIN-SEPARATION.md`](file:///c:/xampp/htdocs/UKRIDA_LabReserve/Plan/AuthRework/PLAN-EMAIL-LOGIN-AND-ADMIN-SEPARATION.md) proposes:

| Change | Description |
|--------|-------------|
| **Drop `email_domain` column** | New migration removes the column from `study_programs`; seeders updated to look up by `name` |
| **Single-step login** | Replace the two-step flow (email → domain detect → dropdown → password) with direct email + password |
| **Admin rejection on `/login`** | After successful auth, if `role === 'admin'`, log out immediately and show error |
| **Remove dead code** | Delete `/login/select` route, `select-user.blade.php` view, `LoginAuthenticateRequest.php` form request, and unused controller methods |
| **Rebuild login view** | Replace the two-step UI with a single email + password form mirroring `admin-login.blade.php` |

Total: **1 new file** (migration), **8 modified files**, **2 deleted files**.

---

## 3. Strengths — What the Plan Does Well

### 3.1 Problem–Solution Alignment ✅

Every proposed change traces directly to one of the two supervisor complaints. There is no speculative "while we're at it" scope creep. This is exactly the kind of surgical approach that keeps risk low.

### 3.2 Exhaustive Current-State Documentation ✅

The plan documents the **current** behavior with exact line numbers, route tables, and method signatures from the actual codebase (`AuthenticatedSessionController.php` lines 33–63, 82–86, 94–128, 133–176). This makes it reviewable without needing to open the source code.

### 3.3 Complete Code Samples ✅

Every modified file has a copy-pasteable code snippet showing exactly what the new code should look like. This eliminates ambiguity during implementation.

### 3.4 Migration Safety ✅

- The `down()` method re-adds `email_domain` as `nullable` (not `NOT NULL`), preventing rollback failure on existing rows.
- The unique index is dropped **before** the column itself (MySQL requires this order).
- The plan correctly notes that rollback only matters during recovery and the old flow won't exist anyway.

### 3.5 Session Compatibility ✅

The plan addresses stale session keys (`login.study_program_id`, `login.email_attempted`) — the new code never reads them, so in-flight sessions from the old flow degrade gracefully. Existing logged-in sessions are unaffected because `users` table auth columns are untouched.

### 3.6 Verification Steps ✅

10 concrete, testable scenarios are listed with expected outcomes:

| # | Scenario | Expected Result |
|---|----------|-----------------|
| 1 | DB migration runs | `email_domain` column gone |
| 2 | Re-seed | No errors |
| 3 | User login happy path | Redirect to `/dashboard` |
| 4 | Admin rejected at `/login` | Error message, not logged in |
| 5 | Admin login happy path | Redirect to `/admin/dashboard` |
| 6 | Non-admin rejected at `/admin/login` | Error message, not logged in |
| 7 | Gmail-style email works | Login succeeds |
| 8 | Old two-step URLs return 404 | `/login/select` and `/login/authenticate` dead |
| 9 | Rate limiting still works | 5 attempts then blocked |
| 10 | Admin user panel still renders | `study_program_id` dropdown unaffected |

This is a solid verification checklist — each step has a clear pass/fail condition.

### 3.7 Risk Assessment ✅

Honest risk table with mitigations. The highest risk (dropping a column) is rated "Medium" and mitigated by the rollback migration + backup recommendation.

### 3.8 Explicit Out-of-Scope Boundary ✅

The plan lists 5 items that are explicitly **not** being touched (password reset, 2FA, removing `study_program_id`, etc.), preventing future confusion about what this change was supposed to cover.

---

## 4. Suggested Improvements

### 4.1 Login Placeholder Text (Minor — UX)

**Current:** The plan's `login.blade.php` snippet uses `placeholder="nama@gmail.com"`.

**Issue:** Since institutional emails like `budi@ti.ukrida.ac.id` still work, a Gmail-only placeholder might confuse users into thinking only Gmail is accepted.

**Suggestion:** Use a more neutral placeholder:
```html
placeholder="Alamat email Anda"
```
Or keep a concrete example but make it generic:
```html
placeholder="nama@email.com"
```

### 4.2 Rate Limiter Helper Cleanup (Minor — Code Hygiene)

**Current:** The plan says to delete `ensureIsNotRateLimited()` and `throttleKey()` (the old two-step helpers) and keep `ensureIsNotRateLimitedByEmail()` and `throttleKeyByEmail()`.

**Suggestion:** Explicitly verify that `adminAuthenticate()` uses the same `ensureIsNotRateLimitedByEmail()` / `throttleKeyByEmail()` helpers. If it has its own separate rate limiter (which it likely does — the plan mentions a "separate limiter for admin login" in the handover doc), confirm that removing the old helpers doesn't affect admin login. A quick grep for `ensureIsNotRateLimited` (without the `ByEmail` suffix) across the controller would confirm.

### 4.3 Import Cleanup Confirmation (Minor — Code Hygiene)

**Current:** The plan says to delete `LoginAuthenticateRequest.php` but doesn't explicitly mention removing its `use` import from `AuthenticatedSessionController.php`.

**Suggestion:** Add a note:
```
After deleting LoginAuthenticateRequest.php, remove:
  use App\Http\Requests\Auth\LoginAuthenticateRequest;
from AuthenticatedSessionController.php (if present).
```

This is minor since PHP would throw a clear error if the import references a missing class, but explicitly noting it prevents confusion during implementation.

### 4.4 Named Route Reference Check (Minor — Safety)

**Current:** The plan says "Any view that links to [the old routes] must be updated (none expected)."

**Suggestion:** Before executing, run a project-wide grep to confirm:
```bash
grep -r "login.select\|login.authenticate\|login/select\|login/authenticate" resources/ routes/ app/
```
If any references exist outside the files being deleted/modified, they need updating. The plan's assertion is almost certainly correct, but the grep takes 2 seconds and eliminates the "almost."

### 4.5 `is_active` Check Ordering (Minor — Logic)

**Current:** In the proposed `authenticate()` method, the admin check comes before the `is_active` check:

```php
// 1. Check admin → reject
if ($user->role === 'admin') { ... }

// 2. Check is_active → reject
if (! $user->is_active) { ... }
```

**Consideration:** This ordering means a deactivated admin who tries `/login` sees "Akun admin harus masuk melalui portal admin" instead of "Akun Anda nonaktif." This is actually **correct behavior** — the message should guide them to the right login portal regardless of their active status. The `is_active` check is handled separately at `/admin/login` by the `ActiveUserOnly` middleware.

**Verdict:** The ordering is correct as written. No change needed.

### 4.6 `StudyProgramSeeder` — Consider `firstOrCreate` Guard (Optional — Robustness)

**Current:** The plan uses `updateOrCreate` keyed on `name`.

**Consideration:** If two study programs ever share the same name (unlikely but possible in a multi-faculty scenario), `updateOrCreate` on `name` would silently merge them. Currently the 5 seeded names are unique, so this is a non-issue.

**Verdict:** Acceptable as-is. Only flag this if the university adds programs with duplicate names in the future.

---

## 5. Files Not Mentioned But Worth Checking

These files are **not** in the plan's change list. I recommend a quick scan to confirm they have no `email_domain` references:

| File / Directory | Why Check |
|------------------|-----------|
| `app/Http/Controllers/Admin/AdminUserController.php` | Creates/edits users — might reference `email_domain` in validation or display |
| `resources/views/admin/users/` | User create/edit forms — might show `email_domain` field |
| `resources/views/admin/teams/` | Team forms — might reference study program domain |
| `config/` | Any config referencing `email_domain` |
| `tests/` | Any test fixtures or factories referencing `email_domain` |

If none of these reference `email_domain`, the plan's file list is complete.

---

## 6. Risk Matrix — My Assessment

| Change | Plan's Rating | My Rating | Notes |
|--------|--------------|-----------|-------|
| Drop `email_domain` column | Medium | **Medium** | Agree — irreversible data loss if the column had meaningful values; mitigated by rollback migration |
| Removing two-step routes | Low | **Low** | Agree — no external links depend on them |
| Admin rejection on `/login` | Low | **Low** | Agree — mirrors the existing pattern at `/admin/login` |
| Replacing login view | Low | **Low** | Agree — strict simplification, not a redesign |
| Seeder lookup by `name` | Low | **Low** | Agree — names are unique in seed data |
| **Overall execution risk** | — | **Low** | Well-scoped, well-documented, all edge cases addressed |

---

## 7. Final Verdict

### ✅ The plan is well-structured, thorough, and safe to execute.

**Key qualities:**
- Every change traces to the supervisor's request — no unnecessary scope
- Complete code samples eliminate implementation ambiguity
- Migration is reversible
- 10 verification steps with clear pass/fail criteria
- Explicit out-of-scope boundary prevents creep

**Recommended pre-execution steps:**
1. Run the grep from §4.4 to confirm no stray references to deleted routes
2. Quick-scan the files from §5 for any `email_domain` references
3. Consider the placeholder text change from §4.1 (minor UX improvement)
4. Back up the database before running the migration (standard practice)

**After those checks, the plan can be executed as written.**

---

## 8. Clarification — About `agent_prompt_project_summary.md`

The file [`agent_prompt_project_summary.md`](file:///c:/xampp/htdocs/UKRIDA_LabReserve/Plan/ProjectSummary/agent_prompt_project_summary.md) is **not a plan for the auth rework**. It is a **prompt template** that instructs an AI agent to generate a `PROJECT_SUMMARY.md` document for the KP internship report. It covers:

- Auditing the full codebase against the planning document
- Categorizing features by implementation status (✅ 🚧 ❌ 🔄)
- Writing a structured project summary with 8 sections

This is a separate deliverable from the auth rework. If you'd like this project summary generated, that can be done independently after (or before) the auth changes.
