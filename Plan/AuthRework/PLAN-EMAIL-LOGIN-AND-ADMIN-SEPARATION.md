# PLAN: Email-Based Login + Admin Login Separation

**Branch:** `CoreBookingBackEnd`
**Date:** 2026-05-26
**Status:** DRAFT — awaiting review

---

## Problem Statement

The supervisor identified two issues with the current authentication system:

1. **Email-domain-based login is brittle.** The login flow currently extracts the domain (e.g., `@ti.ukrida.ac.id`) from the user's email, looks it up in `study_programs.email_domain`, then shows a dropdown of users in that program. The supervisor wants users to be able to log in using ordinary email addresses (Gmail, etc.) — the rigid institutional-domain mapping must go.

2. **Admin accounts are reachable through the regular user login.** If an admin happens to be tied to a study program (per `AdminUserSeeder`, the `admin@ukrida.ac.id` account is tied to the "Administrator" study program), they appear in the dropdown at `/login/select` for any other user from the same domain — leaking admin existence and giving a second attack surface for admin credentials.

The fix must move user login to direct email + password (like the existing admin login) and forbid admin authentication on the regular `/login` route entirely.

---

## Current Behavior

### User login (Lecturer / Team) — two-step flow

`routes/auth.php` lines 12–20:

| Route | Method | Handler |
|---|---|---|
| `GET /login` | — | `AuthenticatedSessionController::create` → `auth.login` |
| `POST /login` | — | `AuthenticatedSessionController::detectStudyProgram` |
| `GET /login/select` | — | `AuthenticatedSessionController::selectUser` → `auth.select-user` |
| `POST /login/authenticate` | — | `AuthenticatedSessionController::authenticate` |

`AuthenticatedSessionController::detectStudyProgram()` (lines 33–63) parses the `@domain` from the input email and queries `StudyProgram::where('email_domain', $domain)`. If found, it stores the program id in session and redirects to `/login/select`, which renders a dropdown of every user in that program — **including admins** (no role filter; see lines 82–86).

`AuthenticatedSessionController::authenticate()` (lines 94–128) authenticates by `['id' => user_id, 'password' => password]` against the user the dropdown narrowed to.

### Admin login — direct email + password (separate flow)

`routes/auth.php` lines 22–26:

| Route | Method | Handler |
|---|---|---|
| `GET /admin/login` | — | inline closure → `auth.admin-login` |
| `POST /admin/login` | — | `AuthenticatedSessionController::adminAuthenticate` |

`adminAuthenticate()` (lines 133–176) uses `Auth::attempt(['email' => ..., 'password' => ...])`, then verifies `$user->role === 'admin'`, else logs out + rejects.

### Data layer

`database/migrations/0001_01_01_000000_create_users_table.php`:
- `study_programs`: `id, name, email_domain (unique), is_active, timestamps` ← `email_domain` is the field driving the broken lookup
- `users`: `id, study_program_id, name, email (unique), password, role, is_active, last_login_at, remember_token, timestamps`

Seeders that reference `email_domain`:
- `StudyProgramSeeder` (seeds the five programs with `@ukrida.ac.id`, `@ti.ukrida.ac.id`, etc.)
- `AdminUserSeeder` (looks up the admin's study program via `where('email_domain', '@ukrida.ac.id')`)
- `TestLecturerSeeder` (same pattern for `@ti.ukrida.ac.id`)

---

## Proposed Behavior

### User login (Lecturer / Team) — single step, email + password

| Route | Method | Handler |
|---|---|---|
| `GET /login` | — | `AuthenticatedSessionController::create` → simplified `auth.login` |
| `POST /login` | — | `AuthenticatedSessionController::authenticate` (renamed/rewritten) |

The form takes `email` + `password` directly. Authentication uses `Auth::attempt(['email' => ..., 'password' => ...])`. **The `/login/select` route and the entire two-step flow are removed.**

After successful authentication, **reject if `role === 'admin'`** — admins must use `/admin/login` only:

```php
if ($user->role === 'admin') {
    Auth::logout();
    throw ValidationException::withMessages([
        'email' => 'Akun admin harus masuk melalui portal admin.',
    ]);
}
```

This is the mirror of what `adminAuthenticate()` already does for non-admins.

### Admin login — unchanged

The admin login route, view, and handler stay exactly as they are. Only `adminAuthenticate()` keeps the "must be admin" gate, so a regular user trying to log in at `/admin/login` is still rejected with the same message.

### Data layer changes

1. **Drop `email_domain` column from `study_programs`.** Programs are now identified by `name` only and used for grouping/filtering (the admin user panel and team panel still reference `study_program_id`); they no longer drive login routing.
2. **Migration** that drops the column safely (with rollback support).
3. **Seeders updated** to no longer write or look up by `email_domain`.

`StudyProgram` keeps `study_program_id` on `users` and on `teams`. The relationship and filtering logic in `AdminUserController`, `AdminTeamController`, etc. is untouched — only the login lookup path changes.

---

## Files to Change

| # | File | Type of change |
|---|---|---|
| 1 | `database/migrations/XXXX_XX_XX_drop_email_domain_from_study_programs.php` | NEW — drops the column |
| 2 | `app/Models/StudyProgram.php` | Remove `email_domain` from `$fillable` |
| 3 | `database/seeders/StudyProgramSeeder.php` | Remove `email_domain` from seed data |
| 4 | `database/seeders/AdminUserSeeder.php` | Lookup `StudyProgram` by `name` instead of `email_domain` |
| 5 | `database/seeders/TestLecturerSeeder.php` | Same — lookup by `name` |
| 6 | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Rewrite to single-step email+password; reject admins |
| 7 | `app/Http/Requests/Auth/LoginEmailRequest.php` | REPURPOSE → require `email` + `password`, drop the old two-step assumption |
| 8 | `app/Http/Requests/Auth/LoginAuthenticateRequest.php` | DELETE — no longer used |
| 9 | `routes/auth.php` | Remove `/login/select`, `/login/authenticate`; collapse to `GET/POST /login` |
| 10 | `resources/views/auth/login.blade.php` | Replace two-step UI with a single email + password form |
| 11 | `resources/views/auth/select-user.blade.php` | DELETE |

---

## Detailed Changes

### 1. New migration — `drop_email_domain_from_study_programs`

Create `database/migrations/2026_05_26_000000_drop_email_domain_from_study_programs.php`:

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
            $table->dropUnique(['email_domain']);
            $table->dropColumn('email_domain');
        });
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->string('email_domain')->nullable()->unique()->after('name');
        });
    }
};
```

Note: `down()` adds the column back as `nullable` (rather than failing because old rows have no value), and reapplies the unique index.

### 2. `StudyProgram.php`

```php
protected $fillable = ['name', 'is_active'];   // was: ['name', 'email_domain', 'is_active']
```

### 3. `StudyProgramSeeder.php`

```php
$programs = [
    ['name' => 'Administrator'],
    ['name' => 'Teknik Informatika'],
    ['name' => 'Sistem Informasi'],
    ['name' => 'Teknik Elektro'],
    ['name' => 'Teknik Industri'],
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

(Plus keep the rest as-is; the admin's `email` field remains `admin@ukrida.ac.id` for backwards compatibility with existing data.)

### 5. `TestLecturerSeeder.php`

```php
$program = StudyProgram::where('name', 'Teknik Informatika')->first();
```

(Same idea — same emails for the test users; only the lookup key changes.)

### 6. `AuthenticatedSessionController.php`

**Delete** `detectStudyProgram()` and `selectUser()`. **Rewrite** `authenticate()` to perform a single-step email+password login with explicit admin rejection.

```php
/**
 * Step 1: Display the login form.
 */
public function create(): View
{
    return view('auth.login');
}

/**
 * POST: authenticate with email + password. Admins are forbidden — they must use /admin/login.
 */
public function authenticate(LoginEmailRequest $request): RedirectResponse
{
    $this->ensureIsNotRateLimitedByEmail($request);

    if (! Auth::attempt(
        ['email' => $request->input('email'), 'password' => $request->input('password')],
        $request->boolean('remember')
    )) {
        RateLimiter::hit($this->throttleKeyByEmail($request));

        throw ValidationException::withMessages([
            'email' => 'Email atau kata sandi tidak cocok.',
        ]);
    }

    $user = Auth::user();

    // Hard separation: admin accounts may not log in here.
    if ($user->role === 'admin') {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => 'Akun admin harus masuk melalui portal admin.',
        ]);
    }

    if (! $user->is_active) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => 'Akun Anda nonaktif. Hubungi admin.',
        ]);
    }

    RateLimiter::clear($this->throttleKeyByEmail($request));

    $user->forceFill(['last_login_at' => now()])->save();
    $request->session()->regenerate();

    return redirect()->intended($this->redirectPathByRole($user->role));
}
```

`adminAuthenticate()`, `destroy()`, `redirectPathByRole()`, `ensureIsNotRateLimitedByEmail()`, and `throttleKeyByEmail()` are kept unchanged. The old `ensureIsNotRateLimited()` and `throttleKey()` helpers (which depended on `login.email_attempted` session state from the two-step flow) are deleted along with the `selectUser` code.

### 7. `LoginEmailRequest.php` — repurpose

```php
public function rules(): array
{
    return [
        'email'    => ['required', 'string', 'email:rfc', 'max:255'],
        'password' => ['required', 'string'],
        'remember' => ['nullable', 'boolean'],
    ];
}

public function messages(): array
{
    return [
        'email.required'    => 'Email wajib diisi.',
        'email.email'       => 'Format email tidak valid.',
        'password.required' => 'Kata sandi wajib diisi.',
    ];
}
```

### 8. `LoginAuthenticateRequest.php` — delete

Used only by the old two-step `authenticate()` (which targeted `user_id` from the dropdown). Remove the file.

### 9. `routes/auth.php`

```php
Route::middleware('guest')->group(function () {
    // User login — single-step email + password (admins rejected here, must use /admin/login)
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'authenticate']);

    // Admin login — separate portal
    Route::get('admin/login', fn() => view('auth.admin-login'))->name('admin.login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'adminAuthenticate'])
        ->name('admin.login.authenticate');
});

// ... rest unchanged
```

The `login.select` and `login.authenticate` named routes disappear. Any view that links to them must be updated (none expected — only `auth.select-user` referenced them, and that view is being deleted).

### 10. `resources/views/auth/login.blade.php`

Replace the two-step UI with a single form that mirrors `admin-login.blade.php` (email + password + remember + submit), keeping the existing "Masuk sebagai Administrator" link to `/admin/login` at the bottom. Remove the step indicator at the top (no longer two steps).

Key form skeleton:

```blade
<form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
    @csrf

    <div class="form-field">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}"
               required autofocus autocomplete="username" class="form-input"
               placeholder="nama@gmail.com">
    </div>

    <div class="form-field" x-data="{ show: false }">
        <label for="password" class="form-label">Kata Sandi</label>
        {{-- (reuse the password input + eye toggle markup from admin-login.blade.php) --}}
    </div>

    <label class="flex items-center gap-2.5 text-sm text-ink-700/80 cursor-pointer">
        <input type="checkbox" name="remember" value="1"
               class="rounded border-rule-strong text-ink-700">
        <span>Ingat saya di perangkat ini</span>
    </label>

    <button type="submit" class="btn-mark btn-lg w-full">Masuk</button>
</form>
```

The "Masuk sebagai Administrator" link block at the bottom stays — it's the only entry point for admins.

### 11. `resources/views/auth/select-user.blade.php`

Delete. No other view references it (verified via grep — only the deleted controller method rendered it).

---

## Migration & Backwards Compatibility

### Existing accounts
- All existing user accounts (`admin@ukrida.ac.id`, `budi@ti.ukrida.ac.id`, `tim.alpha@ti.ukrida.ac.id`, plus any admin-created accounts) continue to work — the `users.email` and `users.password` columns are untouched.
- `study_program_id` on each user is preserved.

### Existing study programs
- The five seeded programs (Administrator, Teknik Informatika, Sistem Informasi, Teknik Elektro, Teknik Industri) are preserved; only `email_domain` is dropped from the row.
- The "Administrator" program is still the foreign-key target for the admin account, but it no longer plays any role in login routing. (It can be deleted in a future cleanup if desired, but that's out of scope here.)

### Migration safety
- The new migration drops a column. Running `php artisan migrate:rollback` re-adds it as nullable — old code that read it would get `null` instead of the original value. Acceptable since rollback is only used during recovery and the old two-step login flow won't exist anyway.
- The unique index on `email_domain` is dropped before the column itself (otherwise MySQL throws).

### Sessions
- Existing logged-in sessions stay valid — the `users` table's auth fields didn't change. Users won't be force-logged-out by this deploy.
- The transient session keys `login.study_program_id` and `login.email_attempted` are no longer written; any stale keys from in-flight sessions will simply be ignored (the new code never reads them).

### Admin user can still be created via the existing admin user panel
- `AdminUserController::store()` hard-codes `role => 'lecturer'` (line 59), so no new admins are created through that flow today. Admins must be created via seeder or manually in the DB. **No change** needed here.

---

## Verification Steps

1. **DB migration:**
   ```
   php artisan migrate
   ```
   Confirm `study_programs` table no longer has `email_domain` column.

2. **Re-seed (optional, for fresh dev DB):**
   ```
   php artisan db:seed --class=StudyProgramSeeder
   php artisan db:seed --class=AdminUserSeeder
   php artisan db:seed --class=TestLecturerSeeder
   ```

3. **User login — happy path:**
   - Visit `/login`.
   - Enter `budi@ti.ukrida.ac.id` + `Test@123` → redirected to `/dashboard`. ✓

4. **User login — admin rejected:**
   - Visit `/login`.
   - Enter `admin@ukrida.ac.id` + `Admin@123` → form shows error "Akun admin harus masuk melalui portal admin." Not logged in. ✓

5. **Admin login — happy path:**
   - Visit `/admin/login`.
   - Enter `admin@ukrida.ac.id` + `Admin@123` → redirected to `/admin/dashboard`. ✓

6. **Admin login — non-admin rejected:**
   - Visit `/admin/login`.
   - Enter `budi@ti.ukrida.ac.id` + `Test@123` → error "Akun ini tidak memiliki akses administrator." Not logged in. ✓

7. **Gmail-style email works:**
   - Create a user via the admin panel with email `someone@gmail.com` + a password.
   - Log in via `/login` with that email + password → redirected to `/dashboard`. ✓
   - (Previously this would have failed at `detectStudyProgram()` because `@gmail.com` is not a registered `email_domain`.)

8. **Old two-step URLs return 404:**
   - `/login/select` and `/login/authenticate` no longer match any route.

9. **Rate limit still works:**
   - Five failed attempts at `/login` with the same email → "Too many attempts" message (Laravel's auth.throttle).

10. **Existing user-management views still render:**
    - `/admin/users` (create/edit) — the `study_program_id` dropdown still populates from `StudyProgramSeeder` rows; the `email_domain` column is no longer referenced by any view. ✓

---

## Risk Assessment

| Change | Risk | Mitigation |
|---|---|---|
| Drop `email_domain` column | **Medium** | Migration `down()` re-adds as nullable; backups recommended before prod deploy |
| Removing two-step routes | **Low** | No other view links to them (verified); 404 is acceptable for old bookmarks |
| Admin rejection on `/login` | **Low** | Identical pattern to existing admin-portal rejection of non-admins — well-tested mirror |
| Replacing login view | **Low** | New view is a strict simplification; visual parity with `admin-login.blade.php` |
| Seeder lookup by `name` | **Low** | Names are unique-by-convention in the seed data; if a duplicate is ever introduced, `->first()` is deterministic by insertion order |

---

## Out of Scope (Future Work)

- Allowing admins to be created through the admin panel (currently requires a seeder or manual DB insert).
- Password reset flow (`PasswordController` is untouched; it already uses email lookups).
- Two-factor authentication.
- Removing `study_program_id` from `users` (the supervisor only asked to drop email-domain-based login, not the program association itself).
- Renaming the "Administrator" study program or detaching the admin account from it.

---

## File Change Summary

```
NEW:
  database/migrations/2026_05_26_000000_drop_email_domain_from_study_programs.php

MODIFIED:
  app/Models/StudyProgram.php                                  (remove 'email_domain' from fillable)
  database/seeders/StudyProgramSeeder.php                      (drop email_domain from seed data)
  database/seeders/AdminUserSeeder.php                         (lookup by name)
  database/seeders/TestLecturerSeeder.php                      (lookup by name)
  app/Http/Controllers/Auth/AuthenticatedSessionController.php (single-step + admin rejection)
  app/Http/Requests/Auth/LoginEmailRequest.php                 (require email+password+remember)
  routes/auth.php                                              (drop login/select + login/authenticate)
  resources/views/auth/login.blade.php                         (single-step email+password form)

DELETED:
  app/Http/Requests/Auth/LoginAuthenticateRequest.php
  resources/views/auth/select-user.blade.php
```

No new files in `app/`. No model column adds. No changes to `BookingService`, `BookingController`, or any non-auth feature.
