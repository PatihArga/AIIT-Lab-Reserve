# PLAN — 2-Step Auth: Move Password to Step 1

**Date:** 2026-06-03  
**Status:** PLANNED — not yet executed  
**Requested by:** Supervisor (via Project Manager)

---

## 1. What Changes and Why

### Old flow
| Step | User enters | Validated against |
|------|-------------|-------------------|
| 1 | Program Gmail | `study_programs.email` |
| 2 | Select user + **password** | **`users.password`** via `Auth::attempt` |

### New flow
| Step | User enters | Validated against |
|------|-------------|-------------------|
| 1 | Program Gmail + **password** | `study_programs.email` + **`study_programs.password`** |
| 2 | Select user (no password) | `users.id` + `study_program_id` only |

### Architectural implication
The password moves from **individual-user level** to **program level**.
All lecturers and teams in "Teknik Informatika" share one password stored on the `study_programs` row.
Step 2 becomes a pure selection — once the program password is verified, the selected user is logged in directly with `Auth::login()` (no `Hash::check` on the user record).

> Admin login is completely separate (`/admin/login`) and unchanged.

---

## 2. Database prerequisite (already done)

Migration `2026_06_03_000001_add_password_to_study_programs` has already been run.
`study_programs.password` column exists (nullable VARCHAR, hidden in model).

**Remaining gap:** The 4 live study-program rows have `password = NULL`.
The seeder will set a default password (`Test@123`) so testing works immediately after running `db:seed`.
In production the admin sets real program passwords via the admin Settings or direct DB update.

---

## 3. Files to change

### A — Form request: `LoginEmailRequest.php`
**Add `password` field validation.**

```php
// Before
public function rules(): array
{
    return [
        'email' => ['required', 'string', 'email:rfc', 'max:255'],
    ];
}

// After
public function rules(): array
{
    return [
        'email'    => ['required', 'string', 'email:rfc', 'max:255'],
        'password' => ['required', 'string'],
    ];
}

// messages() — add:
'password.required' => 'Kata sandi program studi wajib diisi.',
```

---

### B — Form request: `LoginAuthenticateRequest.php`
**Remove `password` field. Keep `user_id` and `remember`.**

```php
// Before
public function rules(): array
{
    return [
        'user_id'  => ['required', 'integer', 'exists:users,id'],
        'password' => ['required', 'string', 'min:6'],
        'remember' => ['nullable', 'boolean'],
    ];
}

// After
public function rules(): array
{
    return [
        'user_id'  => ['required', 'integer', 'exists:users,id'],
        'remember' => ['nullable', 'boolean'],
    ];
}

// Remove password messages too.
```

---

### C — Controller: `AuthenticatedSessionController.php`

#### `detectStudyProgram()` — add rate limiting + program password check

```php
public function detectStudyProgram(LoginEmailRequest $request): RedirectResponse
{
    $email = strtolower(trim($request->input('email')));

    // 1. Rate-limit on program Gmail + IP (same key as admin login uses)
    $this->ensureIsNotRateLimitedByEmail($request);

    $program = StudyProgram::where('email', $email)
        ->where('is_active', true)
        ->first();

    if (! $program) {
        RateLimiter::hit($this->throttleKeyByEmail($request));
        throw ValidationException::withMessages([
            'email' => 'Gmail program studi tidak terdaftar.',
        ]);
    }

    // 2. Check program password (new)
    if (! $program->password || ! Hash::check($request->input('password'), $program->password)) {
        RateLimiter::hit($this->throttleKeyByEmail($request));
        throw ValidationException::withMessages([
            'password' => 'Kata sandi program studi tidak cocok.',
        ]);
    }

    $hasUsers = User::where('study_program_id', $program->id)
        ->where('is_active', true)
        ->where('role', '!=', 'admin')
        ->exists();

    if (! $hasUsers) {
        throw ValidationException::withMessages([
            'email' => 'Belum ada akun aktif pada program studi ini. Hubungi admin.',
        ]);
    }

    RateLimiter::clear($this->throttleKeyByEmail($request));

    $request->session()->put('login.study_program_id', $program->id);
    $request->session()->put('login.email_attempted', $email);

    return redirect()->route('login.select');
}
```

#### `authenticate()` — remove password check, use `Auth::login()` directly

```php
public function authenticate(LoginAuthenticateRequest $request): RedirectResponse
{
    $programId = $request->session()->get('login.study_program_id');

    if (! $programId) {
        return redirect()->route('login');
    }

    // No rate-limiting here — password was already verified in Step 1.
    // Just confirm the user belongs to the authenticated program.
    $user = User::where('id', $request->input('user_id'))
        ->where('study_program_id', $programId)
        ->where('is_active', true)
        ->first();

    if (! $user) {
        throw ValidationException::withMessages([
            'user_id' => 'Pengguna yang dipilih tidak valid.',
        ]);
    }

    Auth::login($user, $request->boolean('remember'));

    $user->forceFill(['last_login_at' => now()])->save();

    $request->session()->regenerate();
    $request->session()->forget(['login.study_program_id', 'login.email_attempted']);

    return redirect()->intended($this->redirectPathByRole($user->role));
}
```

#### Rate-limiting direction
- Old: `ensureIsNotRateLimited()` (uses session `login.email_attempted`) called in `authenticate()`.
- New: `ensureIsNotRateLimitedByEmail()` (reads `email` from request directly) called in `detectStudyProgram()`.
- `ensureIsNotRateLimited()` and `throttleKey()` become unused — remove them.

---

### D — View: `auth/login.blade.php` (Step 1)

**Add password field with show/hide toggle after the Gmail field. Update step 2 label.**

Step indicator label change:
```blade
{{-- Before --}}
<span ...>Pilih Nama</span>

{{-- After --}}
<span ...>Pilih Akun</span>
```

New password field (insert after the email `form-field` div, before the submit button):
```blade
<div class="form-field" x-data="{ show: false }">
    <label for="password" class="form-label">Kata Sandi Program Studi</label>
    <div class="relative">
        <input
            id="password"
            name="password"
            :type="show ? 'text' : 'password'"
            required
            autocomplete="current-password"
            class="form-input pr-12"
            placeholder="••••••••"
        />
        <button type="button" @click="show = !show"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-ink-700/50 hover:text-ink-700 transition-colors focus:outline-none">
            {{-- eye-open icon (x-show="!show") --}}
            {{-- eye-closed icon (x-show="show" x-cloak) --}}
        </button>
    </div>
</div>
```

---

### E — View: `auth/select-user.blade.php` (Step 2)

**Remove the password field and remember-me checkbox. Keep only the user dropdown.**

- Delete the `<div class="form-field" x-data="{ show: false }">` password block (lines 47-71).
- Delete the `<label class="flex items-center ...">Ingat saya...</label>` block.
- The `<select name="user_id">` stays.
- The submit button text stays ("Masuk").
- The "← Gunakan gmail lain" back link stays.

> Step indicator: already shows "Pilih Nama" (step 2 active) — update label to "Pilih Akun" for consistency with login.blade.php.

---

### F — Model: `StudyProgram.php`
**Add `hashed` cast so assigning a plain-text password auto-hashes it.**

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'password'  => 'hashed',      // ← add this
    ];
}
```

This means `StudyProgram::create(['password' => 'Test@123'])` stores the hash automatically.
`Hash::check('Test@123', $program->password)` verifies correctly.

---

### G — Seeder: `StudyProgramSeeder.php`
**Add a default program password so fresh installs work immediately.**

```php
$programs = [
    ['name' => 'Teknik Informatika', 'email' => 'ti.ukrida@gmail.com', 'password' => 'Test@123'],
    ['name' => 'Sistem Informasi',   'email' => 'si.ukrida@gmail.com', 'password' => 'Test@123'],
    ['name' => 'Teknik Elektro',     'email' => 'te.ukrida@gmail.com', 'password' => 'Test@123'],
    ['name' => 'Teknik Industri',    'email' => 'tk.ukrida@gmail.com', 'password' => 'Test@123'],
];
```

> **Live database:** The 4 existing rows have `password = NULL`.
> After implementing, run `php artisan db:seed --class=StudyProgramSeeder` OR update them directly:
> ```sql
> UPDATE study_programs SET password = '<bcrypt-hash-of-Test@123>';
> ```

---

## 4. No-change files

| File | Why untouched |
|------|---------------|
| `routes/auth.php` | Route names and verbs unchanged |
| `auth/admin-login.blade.php` | Separate flow, completely independent |
| `AdminDashboardController`, admin routes | Unchanged |
| `users.password` column | Not removed — still used by admin (and can be used for future features). Just no longer used in the user login flow. |

---

## 5. Execution order

1. **Model** — Add `hashed` cast to `StudyProgram` (required before seeder can hash).
2. **Seeder** — Update `StudyProgramSeeder` with passwords.
3. **Run seeder** — `php artisan db:seed --class=StudyProgramSeeder` (populates the 4 live NULL rows).
4. **Form requests** — Update `LoginEmailRequest` (add password), `LoginAuthenticateRequest` (remove password).
5. **Controller** — Rewrite `detectStudyProgram()` + `authenticate()`, remove now-unused `ensureIsNotRateLimited()` + `throttleKey()`.
6. **Views** — Update `login.blade.php` (add password field), `select-user.blade.php` (remove password field).
7. **Verify** — Manual smoke test of all three paths (see §6).

---

## 6. Acceptance criteria

| # | Test | Expected |
|---|------|----------|
| 1 | Step 1: wrong program Gmail | Error on `email` field: "Gmail program studi tidak terdaftar." |
| 2 | Step 1: correct Gmail + wrong password | Error on `password` field: "Kata sandi program studi tidak cocok." |
| 3 | Step 1: correct Gmail + correct password | Redirected to Step 2 (user dropdown only, no password field) |
| 4 | Step 2: select valid user → Masuk | Logged in, redirected to `/dashboard` |
| 5 | Step 2: tamper `user_id` to another program's user | Error: "Pengguna yang dipilih tidak valid." |
| 6 | Step 1: 5 wrong attempts from same IP | Rate-limited (throttle error) |
| 7 | Direct navigation to `/login/select` without Step 1 | Redirected back to `/login` |
| 8 | Admin login (`/admin/login`) | Unchanged — still works with admin Gmail + password |
| 9 | "← Gunakan gmail lain" on Step 2 | Returns to Step 1 |

---

## 7. Updated credentials table (post-implementation)

| Role | Portal | Step 1 | Step 2 | Password |
|------|--------|--------|--------|----------|
| Lecturer | `/login` | `ti.ukrida@gmail.com` + `Test@123` | Select `Dr. Budi Santoso` | — |
| Team | `/login` | `ti.ukrida@gmail.com` + `Test@123` | Select `Tim Alpha` | — |
| Admin | `/admin/login` | `admin.ukrida@gmail.com` | — | `Admin@123` |
