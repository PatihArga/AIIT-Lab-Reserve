# PLAN: Study Program Gmail Login + Admin Gmail Login

**Branch:** `CoreBookingBackEnd`  
**Date:** 2026-05-26  
**Status:** DRAFT — awaiting review

---

## Problem Statement

The supervisor wants two changes to the authentication system:

1. **Replace the institutional-domain lookup with a study program Gmail lookup.**  
   Currently: user enters their own institutional email → system extracts the `@domain` suffix → looks up `study_programs.email_domain`.  
   New: user enters the **study program's Gmail address** (e.g. `ti.ukrida@gmail.com`) → system does an exact match on `study_programs.email`.  
   The two-step flow shape is unchanged — only what the user types in Step 1 and how the controller looks it up change.

2. **Admin login uses a Gmail stored on the admin's user record.**  
   A new `users.gmail` column (nullable, admin-only) stores the admin's Gmail. The `/admin/login` form takes that Gmail + password. Regular users have `gmail = NULL`.

---

## Two-Step Auth — Full Breakage Analysis

Every file that touches the two-step auth flow, mapped against what changes:

| File | Current reference | After change | Action |
|---|---|---|---|
| `AuthenticatedSessionController.php` | `StudyProgram::where('email_domain', $domain)` — extracts domain suffix first | `StudyProgram::where('email', $email)` — exact full-address match | **Modify** |
| `AuthenticatedSessionController.php` | `where('is_active', true)->exists()` for hasUsers — includes admin | Add `->where('role', '!=', 'admin')` | **Modify** |
| `AuthenticatedSessionController.php` | `selectUser()` — `FIELD(role, 'lecturer', 'team', 'admin')` includes admin | Remove `'admin'` from sort; add `->where('role', '!=', 'admin')` | **Modify** |
| `AuthenticatedSessionController.php` | `adminAuthenticate()` — `Auth::attempt(['email' => ...])` | Manual `User::where('gmail', ...)->where('role','admin')` + `Hash::check` | **Modify** |
| `StudyProgram.php` | `$fillable` has `'email_domain'` | Change to `'email'` | **Modify** |
| `StudyProgramSeeder.php` | Seeds `email_domain` with domain suffixes | Seeds `email` with full Gmail addresses | **Modify** |
| `AdminUserSeeder.php` | Lookup by `email_domain` | Lookup by `name`; add `gmail` field | **Modify** |
| `TestLecturerSeeder.php` | Lookup by `email_domain` | Lookup by `name` | **Modify** |
| `login.blade.php` | Label "Email Program Studi", placeholder `nama@ti.ukrida.ac.id` | Label "Gmail Program Studi", placeholder `nama.prodi@gmail.com` | **Modify** |
| `login.blade.php` | Step indicator label "Email" | "Gmail Program Studi" | **Modify** |
| `select-user.blade.php` | Completed-step label "Email" (line 10) | "Gmail Program Studi" | **Modify** |
| `select-user.blade.php` | Back link "← Gunakan email lain" (line 88) | "← Gunakan gmail lain" | **Modify** |
| `admin-login.blade.php` | `name="email"`, label "Email Admin", placeholder `admin@ukrida.ac.id` | `name="gmail"`, label "Gmail Admin", placeholder `nama@gmail.com` | **Modify** |
| `User.php` | `$fillable` missing `'gmail'` | Add `'gmail'` | **Modify** |
| `routes/auth.php` | All routes intact | No change needed | **Unchanged** |
| `LoginEmailRequest.php` | Validates `email` field | No change — input name stays `email` | **Unchanged** |
| `LoginAuthenticateRequest.php` | Validates `user_id` + `password` | No change | **Unchanged** |
| Admin views (`users/`, `teams/`) | All use `study_program_id` FK only | No change — `email_domain` never referenced | **Unchanged** |

---

## Current `study_programs` Table

```
id | name                | email_domain         | created_at | updated_at
 1 | Administrator       | @ukrida.ac.id        | ...        | ...
 2 | Teknik Informatika  | @ti.ukrida.ac.id     | ...        | ...
 3 | Sistem Informasi    | @si.ukrida.ac.id     | ...        | ...
 4 | Teknik Elektro      | @te.ukrida.ac.id     | ...        | ...
 5 | Teknik Industri     | @tk.ukrida.ac.id     | ...        | ...
```

## New `study_programs` Table

```
id | name                | email                    | created_at | updated_at
 1 | Administrator       | admin.ukrida@gmail.com   | ...        | ...
 2 | Teknik Informatika  | ti.ukrida@gmail.com      | ...        | ...
 3 | Sistem Informasi    | si.ukrida@gmail.com      | ...        | ...
 4 | Teknik Elektro      | te.ukrida@gmail.com      | ...        | ...
 5 | Teknik Industri     | tk.ukrida@gmail.com      | ...        | ...
```

`email_domain` renamed to `email`. Values change from bare domain suffixes to full Gmail addresses. Unique constraint preserved by `renameColumn`.

---

## Login Flow (Before vs. After)

### Step 1 — Before
```
User types:   budi@ti.ukrida.ac.id
Controller:   extract "@ti.ukrida.ac.id" → WHERE email_domain = "@ti.ukrida.ac.id"
```

### Step 1 — After
```
User types:   ti.ukrida@gmail.com
Controller:   WHERE email = "ti.ukrida@gmail.com"  (exact match, no extraction)
```

### Step 2 — Unchanged
```
User selects: name from dropdown (admin accounts hidden)
User types:   password
Controller:   authenticate() — unchanged
```

### Admin login — Before
```
User types:   admin@ukrida.ac.id  (field name: email)
Controller:   Auth::attempt(['email' => ..., 'password' => ...])
```

### Admin login — After
```
User types:   admin.ukrida@gmail.com  (field name: gmail)
Controller:   User::where('gmail', input)->where('role','admin') + Hash::check(password)
```

---

## Files to Change (Complete List)

| # | File | Change |
|---|---|---|
| 1 | `database/migrations/2026_05_26_000001_rename_email_domain_to_email_in_study_programs.php` | NEW |
| 2 | `database/migrations/2026_05_26_000002_add_gmail_to_users.php` | NEW |
| 3 | `app/Models/StudyProgram.php` | `'email_domain'` → `'email'` in `$fillable` |
| 4 | `app/Models/User.php` | Add `'gmail'` to `$fillable` |
| 5 | `database/seeders/StudyProgramSeeder.php` | `email` key; Gmail addresses |
| 6 | `database/seeders/AdminUserSeeder.php` | Lookup by `name`; add `gmail` |
| 7 | `database/seeders/TestLecturerSeeder.php` | Lookup by `name` |
| 8 | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | See §8 below |
| 9 | `resources/views/auth/login.blade.php` | Label + placeholder + step indicator label |
| 10 | `resources/views/auth/select-user.blade.php` | Completed-step label + back link text |
| 11 | `resources/views/auth/admin-login.blade.php` | Field name/label/placeholder → gmail |

---

## Detailed Changes

### 1. Migration — rename `email_domain` → `email` in `study_programs`

```php
// database/migrations/2026_05_26_000001_rename_email_domain_to_email_in_study_programs.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->renameColumn('email_domain', 'email');
        });
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->renameColumn('email', 'email_domain');
        });
    }
};
```

`renameColumn` preserves the column's type (`string`), nullable state, and unique index intact.

### 2. Migration — add `gmail` to `users`

```php
// database/migrations/2026_05_26_000002_add_gmail_to_users.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gmail')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['gmail']);
            $table->dropColumn('gmail');
        });
    }
};
```

Nullable — only admin accounts fill this column. Unique — no two admins share the same Gmail.

### 3. `StudyProgram.php`

```php
protected $fillable = ['name', 'email', 'is_active'];
```

### 4. `User.php`

```php
protected $fillable = [
    'name', 'email', 'gmail', 'password', 'role',
    'study_program_id', 'is_active', 'last_login_at',
];
```

### 5. `StudyProgramSeeder.php`

```php
$programs = [
    ['name' => 'Administrator',      'email' => 'admin.ukrida@gmail.com'],
    ['name' => 'Teknik Informatika', 'email' => 'ti.ukrida@gmail.com'],
    ['name' => 'Sistem Informasi',   'email' => 'si.ukrida@gmail.com'],
    ['name' => 'Teknik Elektro',     'email' => 'te.ukrida@gmail.com'],
    ['name' => 'Teknik Industri',    'email' => 'tk.ukrida@gmail.com'],
];

foreach ($programs as $program) {
    StudyProgram::updateOrCreate(
        ['name' => $program['name']],
        array_merge($program, ['is_active' => true])
    );
}
```

### 6. `AdminUserSeeder.php`

```php
$adminProgram = StudyProgram::where('name', 'Administrator')->first();

User::updateOrCreate(
    ['email' => 'admin@ukrida.ac.id'],
    [
        'study_program_id' => $adminProgram?->id,
        'name'      => 'Administrator',
        'password'  => Hash::make('Admin@123'),
        'gmail'     => 'admin.ukrida@gmail.com',   // ← new
        'role'      => 'admin',
        'is_active' => true,
    ]
);
```

> Replace `admin.ukrida@gmail.com` with the real admin Gmail before production.

### 7. `TestLecturerSeeder.php`

```php
$program = StudyProgram::where('name', 'Teknik Informatika')->first();
```

### 8. `AuthenticatedSessionController.php` — four targeted changes

#### 8a. `detectStudyProgram()` — exact match, no domain extraction

Remove the `$atIndex`/`$domain` extraction block. Replace `where('email_domain', $domain)` with `where('email', $email)`. Add `role != admin` to the `$hasUsers` check.

```php
public function detectStudyProgram(LoginEmailRequest $request): RedirectResponse
{
    $email = strtolower(trim($request->input('email')));

    $program = StudyProgram::where('email', $email)
        ->where('is_active', true)
        ->first();

    if (! $program) {
        throw ValidationException::withMessages([
            'email' => 'Gmail program studi tidak terdaftar.',
        ]);
    }

    $hasUsers = User::where('study_program_id', $program->id)
        ->where('is_active', true)
        ->where('role', '!=', 'admin')          // ← add: never land on admin-only program
        ->exists();

    if (! $hasUsers) {
        throw ValidationException::withMessages([
            'email' => 'Belum ada akun aktif pada program studi ini. Hubungi admin.',
        ]);
    }

    $request->session()->put('login.study_program_id', $program->id);
    $request->session()->put('login.email_attempted', $email);

    return redirect()->route('login.select');
}
```

#### 8b. `selectUser()` — filter out admin accounts

```php
$users = User::where('study_program_id', $programId)
    ->where('is_active', true)
    ->where('role', '!=', 'admin')                       // ← add
    ->orderByRaw("FIELD(role, 'lecturer', 'team')")      // ← remove 'admin' from sort
    ->orderBy('name')
    ->get(['id', 'name', 'role']);
```

#### 8c. `adminAuthenticate()` — look up by `users.gmail`

```php
public function adminAuthenticate(Request $request): RedirectResponse
{
    $request->validate([
        'gmail'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ], [
        'gmail.required'    => 'Gmail wajib diisi.',
        'gmail.email'       => 'Format Gmail tidak valid.',
        'password.required' => 'Kata sandi wajib diisi.',
    ]);

    $this->ensureIsNotRateLimitedByEmail($request);

    $admin = User::where('gmail', $request->input('gmail'))
        ->where('role', 'admin')
        ->first();

    if (! $admin || ! Hash::check($request->input('password'), $admin->password)) {
        RateLimiter::hit($this->throttleKeyByEmail($request));

        throw ValidationException::withMessages([
            'gmail' => 'Gmail atau kata sandi tidak cocok.',
        ]);
    }

    if (! $admin->is_active) {
        throw ValidationException::withMessages([
            'gmail' => 'Akun admin nonaktif.',
        ]);
    }

    RateLimiter::clear($this->throttleKeyByEmail($request));

    Auth::login($admin, $request->boolean('remember'));
    $admin->forceFill(['last_login_at' => now()])->save();
    $request->session()->regenerate();

    return redirect()->intended(route('admin.dashboard', absolute: false));
}
```

Add `use Illuminate\Support\Facades\Hash;` to the controller imports.

#### 8d. `throttleKeyByEmail()` — read `gmail` field for admin login

```php
protected function throttleKeyByEmail(Request $request): string
{
    $key = $request->input('gmail') ?? $request->input('email', '');
    return Str::transliterate(Str::lower($key) . '|' . $request->ip());
}
```

### 9. `resources/views/auth/login.blade.php`

Two changes — step indicator label and field label/placeholder:

```blade
{{-- Step indicator (line ~6): was "Email" --}}
<span class="... text-ink-900">Gmail Program Studi</span>

{{-- Field label (line ~28): was "Email Program Studi" --}}
<label for="email" class="form-label">Gmail Program Studi</label>

{{-- Field placeholder (line ~34): was "nama@ti.ukrida.ac.id" --}}
placeholder="nama.prodi@gmail.com"
```

Field `name="email"` stays unchanged — `LoginEmailRequest` still validates `email`.

### 10. `resources/views/auth/select-user.blade.php`

Two changes — completed-step label and back link text:

```blade
{{-- Completed step label (line 10): was "Email" --}}
<span class="... text-ink-700/50">Gmail Program Studi</span>

{{-- Back link (line 88): was "← Gunakan email lain" --}}
← Gunakan gmail lain
```

No other changes to this view.

### 11. `resources/views/auth/admin-login.blade.php`

Change field name, id, label, and placeholder:

```blade
{{-- Was: id="email", name="email", label="Email Admin", placeholder="admin@ukrida.ac.id" --}}
<label for="gmail" class="form-label">Gmail Admin</label>
<input
    id="gmail"
    name="gmail"
    type="email"
    value="{{ old('gmail') }}"
    placeholder="nama@gmail.com"
    required
    autofocus
    autocomplete="username"
    class="form-input"
/>
```

---

## Deploy Order

```
1. php artisan migrate
   (renames email_domain → email; adds gmail to users)

2. php artisan db:seed --class=StudyProgramSeeder
   (populates study_programs.email with Gmail addresses)

3. php artisan db:seed --class=AdminUserSeeder
   (sets users.gmail on the admin account)
```

> Steps 2 and 3 are required. If migration runs without re-seed, `study_programs.email` rows will have stale values from before the rename.

---

## Verification Steps

1. `study_programs` table: column named `email`, values are full Gmail addresses. ✓
2. `users` table: `gmail` column exists; admin row has it set; lecturer/team rows have `NULL`. ✓
3. **User login Step 1 — correct Gmail:** Enter `ti.ukrida@gmail.com` → redirected to Step 2. ✓
4. **User login Step 1 — wrong Gmail:** Enter `wrong@gmail.com` → "Gmail program studi tidak terdaftar." ✓
5. **User login Step 1 — admin program Gmail:** Enter `admin.ukrida@gmail.com` → "Belum ada akun aktif…" (admin accounts filtered out). ✓
6. **User login Step 2 — no admin in name list:** Name dropdown shows lecturers and teams only. ✓
7. **User login — happy path:** `ti.ukrida@gmail.com` → select name → password → `/dashboard`. ✓
8. **Admin login — correct Gmail + password:** `/admin/login` with `admin.ukrida@gmail.com` + `Admin@123` → `/admin/dashboard`. ✓
9. **Admin login — wrong Gmail:** Error "Gmail atau kata sandi tidak cocok." Not logged in. ✓
10. **Admin login — lecturer Gmail:** `gmail = NULL` → no match → error. ✓
11. **`select-user.blade.php` step label:** Shows "Gmail Program Studi" in the completed step badge. ✓
12. **Admin panel (user/team forms):** Create/edit still works — uses `study_program_id` FK, untouched. ✓
13. **Rate limiting:** 5 failed attempts at either login → throttle message. ✓
14. **Rollback:** `php artisan migrate:rollback --step=2` restores `email_domain` column and drops `gmail`. Old domain-based login works again with old seeder. ✓

---

## Risk Assessment

| Change | Risk | Mitigation |
|---|---|---|
| Rename `email_domain` → `email` | **Low** | `renameColumn` preserves all data and indexes |
| Re-seed required after migration | **Medium** | Documented in deploy order; seeder is idempotent (`updateOrCreate`) |
| Exact match replaces domain extraction | **Low** | Simpler logic — one `where()` instead of string manipulation |
| Add `gmail` to `users` | **Low** | Nullable column; no existing rows affected |
| Admin login uses manual lookup | **Low** | Explicit `role = admin` guard; `Hash::check` is identical security to `Auth::attempt` |

---

## File Change Summary

```
NEW:
  database/migrations/2026_05_26_000001_rename_email_domain_to_email_in_study_programs.php
  database/migrations/2026_05_26_000002_add_gmail_to_users.php

MODIFIED:
  app/Models/StudyProgram.php                                  (fillable: email_domain → email)
  app/Models/User.php                                          (fillable: add gmail)
  database/seeders/StudyProgramSeeder.php                      (email key; Gmail values)
  database/seeders/AdminUserSeeder.php                         (lookup by name; set gmail)
  database/seeders/TestLecturerSeeder.php                      (lookup by name)
  app/Http/Controllers/Auth/AuthenticatedSessionController.php (exact-match lookup; admin excluded from Step 2; adminAuthenticate uses gmail)
  resources/views/auth/login.blade.php                         (step label + field label/placeholder)
  resources/views/auth/select-user.blade.php                   (completed-step label + back link)
  resources/views/auth/admin-login.blade.php                   (field name/label/placeholder → gmail)

DELETED:
  (none)

UNCHANGED:
  routes/auth.php
  app/Http/Requests/Auth/LoginEmailRequest.php
  app/Http/Requests/Auth/LoginAuthenticateRequest.php
  All booking, admin approval, and non-auth files
```
