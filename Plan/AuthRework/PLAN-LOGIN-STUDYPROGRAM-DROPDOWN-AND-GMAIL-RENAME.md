# PLAN: Study Program Dropdown Login + Rename email_domain → gmail

**Branch:** `CoreBookingBackEnd`  
**Date:** 2026-05-26  
**Status:** DRAFT — awaiting review  
**Replaces:** ~~PLAN-EMAIL-LOGIN-AND-ADMIN-SEPARATION.md~~ (that plan was incorrect — it deleted the `email_domain` column entirely instead of renaming it)

---

## Problem Statement

The supervisor raised two issues:

1. **The `email_domain`-based login is too rigid.** Users must own an institutional email matching a registered domain (e.g. `@ti.ukrida.ac.id`). Users with Gmail addresses can never log in because `@gmail.com` isn't a registered domain.

2. **The column name `email_domain` is confusing.** The supervisor wants to rename it to `gmail` so that each study program can store a Gmail contact address instead of a bare domain suffix.

The first plan misread issue #2 as "drop the column entirely" — that was wrong. The correct fix is:
- Rename `email_domain` → `gmail` and store full email addresses (e.g. `teknik.informatika@gmail.com`) instead of bare domain suffixes (e.g. `@ti.ukrida.ac.id`).
- Change the login Step 1 from email-domain detection to a **study program dropdown**, so users are no longer required to own an address at a specific domain.

---

## Current Behavior

### Login Step 1 — email domain detection

User enters their institutional email (e.g. `budi@ti.ukrida.ac.id`). The controller extracts `@ti.ukrida.ac.id`, looks it up in `study_programs.email_domain`, and if found, stores the program id in session and redirects to Step 2.

**Problem:** Any user whose email doesn't match a registered domain (Gmail, personal email, etc.) gets "Domain email tidak terdaftar" and cannot proceed.

### `study_programs.email_domain` column

Stores just the domain suffix: `@ukrida.ac.id`, `@ti.ukrida.ac.id`, etc. The name is confusing and doesn't accommodate full email addresses.

### Admin creates users via the user panel

`AdminUserController::store()` and the create/edit forms do not reference `email_domain` at all — they work with `study_program_id` on the `users` table. So the study program dropdown in the admin panel is completely unaffected by this change.

---

## Proposed Behavior

### Login Step 1 — study program dropdown

Instead of entering an email, the user **selects their study program from a dropdown**. The controller loads the active study programs and renders them. No email-domain lookup required.

```
GET /login → dropdown of active study programs
POST /login → store selected study_program_id in session → redirect to Step 2
```

Step 2 is unchanged: user selects their name from the list, enters password, logs in.

### `study_programs.gmail` column

`email_domain` is renamed to `gmail`. The seeded values change from bare domains to full Gmail-style addresses:

| Program | Old `email_domain` | New `gmail` |
|---|---|---|
| Administrator | `@ukrida.ac.id` | `admin.ukrida@gmail.com` |
| Teknik Informatika | `@ti.ukrida.ac.id` | `ti.ukrida@gmail.com` |
| Sistem Informasi | `@si.ukrida.ac.id` | `si.ukrida@gmail.com` |
| Teknik Elektro | `@te.ukrida.ac.id` | `te.ukrida@gmail.com` |
| Teknik Industri | `@tk.ukrida.ac.id` | `tk.ukrida@gmail.com` |

> The `gmail` column is informational — it is **not used for login routing**. It can be displayed in the admin panel (e.g. "Contact: ti.ukrida@gmail.com") and is available for future features (e.g. sending program-level notifications). Admins can update it via Settings.

### Admin login — unchanged

`POST /admin/login` → `adminAuthenticate()` — direct email + password, must be `role = 'admin'`. Nothing changes here.

---

## Files to Change

| # | File | Change |
|---|---|---|
| 1 | `database/migrations/2026_05_26_000001_rename_email_domain_to_gmail_in_study_programs.php` | NEW — renames column |
| 2 | `app/Models/StudyProgram.php` | Rename `email_domain` → `gmail` in `$fillable` |
| 3 | `database/seeders/StudyProgramSeeder.php` | Use `gmail` key; store full addresses |
| 4 | `database/seeders/AdminUserSeeder.php` | Look up by `name` (no longer by domain) |
| 5 | `database/seeders/TestLecturerSeeder.php` | Look up by `name` |
| 6 | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Replace `detectStudyProgram()` with `showProgramSelect()` and simplify `selectUser()` |
| 7 | `app/Http/Requests/Auth/LoginEmailRequest.php` | REPURPOSE → validate `study_program_id` (integer) instead of `email` |
| 8 | `routes/auth.php` | `POST /login` → now calls `selectProgram()` instead of `detectStudyProgram()` |
| 9 | `resources/views/auth/login.blade.php` | Replace email input with study-program `<select>` dropdown |

No files are deleted. `LoginAuthenticateRequest.php` and `select-user.blade.php` stay as-is.

---

## Detailed Changes

### 1. New migration — rename `email_domain` to `gmail`

`database/migrations/2026_05_26_000001_rename_email_domain_to_gmail_in_study_programs.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->renameColumn('email_domain', 'gmail');
        });
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->renameColumn('gmail', 'email_domain');
        });
    }
};
```

> `renameColumn` preserves the column's data, type (`string`), and nullable/unique constraints.

### 2. `StudyProgram.php`

```php
protected $fillable = ['name', 'gmail', 'is_active'];
```

### 3. `StudyProgramSeeder.php`

```php
$programs = [
    ['name' => 'Administrator',       'gmail' => 'admin.ukrida@gmail.com'],
    ['name' => 'Teknik Informatika',  'gmail' => 'ti.ukrida@gmail.com'],
    ['name' => 'Sistem Informasi',    'gmail' => 'si.ukrida@gmail.com'],
    ['name' => 'Teknik Elektro',      'gmail' => 'te.ukrida@gmail.com'],
    ['name' => 'Teknik Industri',     'gmail' => 'tk.ukrida@gmail.com'],
];

foreach ($programs as $program) {
    StudyProgram::updateOrCreate(
        ['name' => $program['name']],
        array_merge($program, ['is_active' => true])
    );
}
```

### 4. `AdminUserSeeder.php`

```php
$adminProgram = StudyProgram::where('name', 'Administrator')->first();
```

### 5. `TestLecturerSeeder.php`

```php
$program = StudyProgram::where('name', 'Teknik Informatika')->first();
```

### 6. `AuthenticatedSessionController.php`

**Replace** `detectStudyProgram()` with `selectProgram()` — loads the program list and stores the chosen id in session. The rest of the controller (`selectUser()`, `authenticate()`, `adminAuthenticate()`, etc.) is **unchanged**.

```php
/**
 * Step 1 POST: store the selected study program in session and proceed to Step 2.
 */
public function selectProgram(LoginProgramRequest $request): RedirectResponse
{
    $program = StudyProgram::where('id', $request->input('study_program_id'))
        ->where('is_active', true)
        ->firstOrFail();

    $hasUsers = User::where('study_program_id', $program->id)
        ->where('is_active', true)
        ->where('role', '!=', 'admin')
        ->exists();

    if (! $hasUsers) {
        throw ValidationException::withMessages([
            'study_program_id' => 'Belum ada akun aktif pada program studi ini. Hubungi admin.',
        ]);
    }

    $request->session()->put('login.study_program_id', $program->id);

    return redirect()->route('login.select');
}
```

The old `ensureIsNotRateLimited()` / `throttleKey()` helpers (which throttled on `login.email_attempted`) are kept — they are still used by `authenticate()` in Step 2.

The import for `LoginEmailRequest` is swapped for `LoginProgramRequest` (see §7).

### 7. `LoginEmailRequest.php` — repurpose as `LoginProgramRequest`

**Option A (rename file):** Rename to `LoginProgramRequest.php`.  
**Option B (repurpose in-place):** Keep the filename, rename the class inside.

Recommendation: rename the file to avoid confusion.

```php
// New file: app/Http/Requests/Auth/LoginProgramRequest.php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginProgramRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'study_program_id.required' => 'Silakan pilih program studi.',
            'study_program_id.exists'   => 'Program studi tidak valid.',
        ];
    }
}
```

`LoginEmailRequest.php` can then be deleted (it validated only the `email` field for Step 1, which no longer exists).

### 8. `routes/auth.php`

```php
// Step 1: select study program
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'selectProgram']);   // ← was detectStudyProgram

// Step 2: unchanged
Route::get('login/select', [AuthenticatedSessionController::class, 'selectUser'])->name('login.select');
Route::post('login/authenticate', [AuthenticatedSessionController::class, 'authenticate'])->name('login.authenticate');
```

### 9. `resources/views/auth/login.blade.php`

Replace the email input and step description with a study-program dropdown. The step indicator stays (it's still a two-step flow).

```blade
<x-auth-layout title="Masuk">

    {{-- Step indicator --}}
    <div class="flex items-center gap-3 mb-10">
        <div class="step-dot-active">1</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-900">Program Studi</span>
        <div class="step-connector"></div>
        <div class="step-dot-pending">2</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-700/40">Pilih Nama</span>
    </div>

    <div class="page-eyebrow">Masuk</div>
    <h2 class="font-display text-3xl font-bold text-ink-900 tracking-tight">Selamat datang.</h2>

    @if ($errors->any())
        <div class="mt-6 rounded-md border border-status-rejected/30 bg-status-rejected/5 p-3">
            @foreach ($errors->all() as $error)
                <p class="text-sm text-status-rejected">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
        @csrf

        <div class="form-field">
            <label for="study_program_id" class="form-label">Program Studi</label>
            <select id="study_program_id" name="study_program_id" required class="form-select">
                <option value="" disabled selected>Pilih program studi Anda…</option>
                @foreach ($studyPrograms as $sp)
                    <option value="{{ $sp->id }}" @selected(old('study_program_id') == $sp->id)>
                        {{ $sp->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-mark btn-lg w-full">
            Lanjutkan
            <svg class="w-4 h-4" ...>...</svg>
        </button>
    </form>

    {{-- Admin link stays --}}
    ...
</x-auth-layout>
```

The `create()` controller method must also pass `$studyPrograms` to the view:

```php
public function create(): View
{
    $studyPrograms = StudyProgram::where('is_active', true)
        ->whereNotIn('name', ['Administrator'])  // hide the admin program from user login
        ->orderBy('name')
        ->get(['id', 'name']);

    return view('auth.login', compact('studyPrograms'));
}
```

> Hiding "Administrator" from the dropdown is the correct fix for the admin-leakage issue the supervisor originally raised. Admin accounts must use `/admin/login` — they should not be selectable at `/login` at all.

---

## Admin-Leakage Fix (Bonus)

The supervisor originally raised that admin accounts appear in the Step 2 dropdown at `/login/select`. Even with the new dropdown-based Step 1, if "Administrator" program is shown, a user could select it and see admin accounts in the Step 2 list.

**Double fix:**
1. Exclude "Administrator" from the program dropdown in `create()` (see above).
2. In `selectUser()`, add a `where('role', '!=', 'admin')` filter to the user list query — this is a belt-and-suspenders guard so that even if an admin program somehow reaches Step 2, admin accounts are never shown.

Current `selectUser()` already shows all roles including admin via:
```php
->orderByRaw("FIELD(role, 'lecturer', 'team', 'admin')")
```
This should become:
```php
->where('role', '!=', 'admin')
->orderByRaw("FIELD(role, 'lecturer', 'team')")
```

---

## Migration & Backwards Compatibility

### Existing accounts
All user accounts are unaffected — `users.email`, `users.password`, `users.study_program_id` are not touched.

### Existing study programs
The five seeded programs retain their rows. `renameColumn` preserves all existing data. After migration the column is named `gmail` and the seeder updates the values to full Gmail addresses.

### Rollback safety
`down()` renames `gmail` → `email_domain`. The old two-step domain-detection login would work again if re-deployed with the old code.

### Admin panel
`AdminUserController`, `AdminTeamController`, and all admin views reference `study_program_id` (the FK on `users`) — not `email_domain`/`gmail`. They are untouched.

---

## Verification Steps

1. **Run migration:** `php artisan migrate` — confirm `study_programs` has `gmail` column, no `email_domain`.
2. **Re-seed:** `php artisan db:seed --class=StudyProgramSeeder` — confirm `gmail` values are full addresses.
3. **Login Step 1:** Visit `/login` → dropdown shows study programs (no "Administrator"). Select one → redirected to Step 2.
4. **Login Step 2:** Select name, enter password → redirected to `/dashboard`.
5. **Admin not in dropdown:** "Administrator" program does not appear in the `/login` dropdown.
6. **Admin not in user list:** If somehow Step 2 is reached for an admin program, admin users are not shown.
7. **Admin login unaffected:** `/admin/login` still works with `admin@ukrida.ac.id` + `Admin@123`.
8. **Old `/login` URL with email input:** No longer shown — a dropdown replaces it.
9. **Admin panel:** `/admin/users/create` and `/admin/users/{id}/edit` still load study-program dropdowns correctly.
10. **Rate limiting:** 5 failed Step 2 attempts → "Too many login attempts" message.

---

## Risk Assessment

| Change | Risk | Mitigation |
|---|---|---|
| Rename `email_domain` → `gmail` | **Low** | `renameColumn` preserves data; rollback re-renames |
| Replace email input with dropdown | **Low** | Simpler UI; no data loss |
| Seeder values → full Gmail addresses | **Low** | `updateOrCreate` on `name`; no row loss |
| Admin hidden from dropdown | **Low** | Belt-and-suspenders: also filtered in `selectUser()` |

---

## File Change Summary

```
NEW:
  database/migrations/2026_05_26_000001_rename_email_domain_to_gmail_in_study_programs.php
  app/Http/Requests/Auth/LoginProgramRequest.php

MODIFIED:
  app/Models/StudyProgram.php                                  (email_domain → gmail in fillable)
  database/seeders/StudyProgramSeeder.php                      (gmail key; full email addresses)
  database/seeders/AdminUserSeeder.php                         (lookup by name)
  database/seeders/TestLecturerSeeder.php                      (lookup by name)
  app/Http/Controllers/Auth/AuthenticatedSessionController.php (detectStudyProgram → selectProgram; create() passes $studyPrograms; selectUser() excludes admins)
  routes/auth.php                                              (POST /login → selectProgram)
  resources/views/auth/login.blade.php                         (email input → study-program dropdown)

DELETED:
  app/Http/Requests/Auth/LoginEmailRequest.php                 (replaced by LoginProgramRequest)
```

No changes to `BookingService`, `BookingController`, admin controllers, or any non-auth feature.
```
