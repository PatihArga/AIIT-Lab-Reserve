# Pengguna & Tim (Users & Teams) Page

**Project:** UKRIDA / AIIT Lab Reserve System
**Scope:** Admin → Pengguna & Tim (`/admin/users`) — list, add, and edit pages for both **Lecturer** and **Team** accounts
**Audience:** Admin role only
**Date:** 2026-06-23

---

## 1. Overview

The **Pengguna & Tim** section is the admin's account-management hub. From one list the
admin manages two kinds of non-admin accounts:

- **Dosen (Lecturer)** — an individual academic staff account.
- **Tim (Team)** — a student-group account, supervised by a PIC lecturer, that owns a list of student members.

Both account types share the same `users` table (distinguished by `role`), but a Team also
has a companion `teams` row plus `team_members` rows. The page therefore routes editing to
two different controllers/forms depending on the row's role.

The section comprises **five screens**:

| Screen | Route | Controller |
|---|---|---|
| List | `GET /admin/users` | `AdminUserController@index` |
| Add lecturer | `GET /admin/users/create` → `POST /admin/users` | `AdminUserController@create` / `store` |
| Edit lecturer | `GET /admin/users/{user}/edit` → `PUT /admin/users/{user}` | `AdminUserController@edit` / `update` |
| Add team | `GET /admin/teams/create` → `POST /admin/teams` | `AdminTeamController@create` / `store` |
| Edit team | `GET /admin/teams/{team}/edit` → `PUT /admin/teams/{team}` | `AdminTeamController@edit` / `update` |

All routes sit behind the `auth`, `active`, and `admin` middleware.

> **Key authentication note:** Lecturers and Teams do **not** log in with a personal password.
> They authenticate through the two-step *study-program Gmail* flow. Because `users.password`
> is `NOT NULL`, the controllers store an **unusable random hash** at creation. The "Reset
> Password" fields on the edit forms exist mainly as an administrative override and are
> optional.

---

## 2. Usage Guide

### 2.1 The list page

Open **Pengguna & Tim** (`/admin/users`). The header offers two creation buttons:
**Buat Tim** (create team) and **Tambah Dosen** (add lecturer).

**Filtering** (all server-side via GET):

| Control | Parameter | Behaviour |
|---|---|---|
| Role chips | `role` | **Semua** / **Dosen** / **Tim**. Selecting a chip preserves the search term and study-program filter. |
| Search | `q` | Matches against name **or** email (partial). |
| Program studi | `study_program_id` | Limits to one study program. |
| **Terapkan** / **Reset** | — | Apply submits the search+program form; Reset clears `q` and `study_program_id` (keeping the active role). |

**Table columns:** Nama (with PIC lecturer shown beneath team rows), Email, Peran (Dosen/Tim
badge), Program Studi, Reservasi (booking count), Status (Aktif/Nonaktif), and an action link.

**Smart edit routing:** the action link checks the row's role — a team row links to
**Edit Tim** (`admin.teams.edit`), everything else links to **Edit** (`admin.users.edit`).

Results are paginated at **20 per page**, with filters preserved across pages.

### 2.2 Add a lecturer

1. Click **Tambah Dosen**.
2. Fill in **Nama Lengkap**, **Email Institusi** (must be unique), and **Program Studi**.
3. Optionally toggle **Aktifkan akun segera** (on by default; inactive accounts can't log in).
4. Click **Buat Akun Dosen**.

The role is fixed to `lecturer`; no password is entered (the study-program flow handles login).

### 2.3 Add a team

1. Click **Buat Tim**.
2. Fill in **Nama Tim**, **Email Tim** (unique login identity), **Program Studi**, and **PIC (Dosen Penanggung Jawab)** — the dropdown only lists active lecturers.
3. Under **Anggota Tim**, add student rows (each with **Nama Mahasiswa** + **NIM**). Use **Tambah Anggota** to add rows and the ✕ button to remove them (at least one row always remains). **Blank rows are ignored** on save.
4. Click **Buat Tim**.

This creates three things atomically: the `users` row (role `team`), the `teams` row, and one
`team_members` row per filled member.

### 2.4 Edit a lecturer

From the list, click **Edit** on a lecturer row. You can change name, email, study program, and
active status. The **Reset Password** section is optional — leave it blank to keep the current
password; if filled, it must be at least 8 characters and match the confirmation field.

### 2.5 Edit a team

From the list, click **Edit Tim** on a team row. You can change the team name, email, study
program, PIC lecturer, active status, optional password, and the **full member list**. The
member editor is pre-populated with the existing members; on save, the member list is fully
replaced with whatever rows you leave (delete-and-re-insert).

### 2.6 What the admin cannot do here

- **Admin accounts cannot be edited** through this panel — `AdminUserController@edit/update` abort with **403** if the target user is an admin (admins are also excluded from the list query entirely).
- There is **no delete action**; accounts are deactivated (set inactive) rather than removed, preserving their booking history and audit trail.

---

## 3. Implementation

### 3.1 Routes

```php
// routes/web.php — inside the admin group: ->middleware(['auth','active','admin'])->prefix('admin')->name('admin.')
Route::get('/users',              [AdminUserController::class, 'index'])->name('users.index');
Route::get('/users/create',       [AdminUserController::class, 'create'])->name('users.create');
Route::post('/users',             [AdminUserController::class, 'store'])->name('users.store');
Route::get('/users/{user}/edit',  [AdminUserController::class, 'edit'])->name('users.edit');
Route::put('/users/{user}',       [AdminUserController::class, 'update'])->name('users.update');

Route::get('/teams/create',       [AdminTeamController::class, 'create'])->name('teams.create');
Route::post('/teams',             [AdminTeamController::class, 'store'])->name('teams.store');
Route::get('/teams/{team}/edit',  [AdminTeamController::class, 'edit'])->name('teams.edit');
Route::put('/teams/{team}',       [AdminTeamController::class, 'update'])->name('teams.update');
```

### 3.2 List controller — `AdminUserController@index`

**File:** [app/Http/Controllers/Admin/AdminUserController.php](../app/Http/Controllers/Admin/AdminUserController.php)

```php
public function index(Request $request): View
{
    $query = User::with(['studyProgram', 'teamAccount.picLecturer'])
        ->withCount('bookings')              // populates $user->bookings_count
        ->where('role', '!=', 'admin')       // admins never appear in this list
        ->orderBy('name');

    if ($request->filled('role') && $request->role !== 'all') {
        $query->where('role', $request->role);
    }
    if ($request->filled('study_program_id') && $request->study_program_id !== 'all') {
        $query->where('study_program_id', $request->study_program_id);
    }
    if ($request->filled('q')) {
        $term = $request->q;
        $query->where(function ($q) use ($term) {      // grouped OR so it doesn't
            $q->where('name',  'like', '%' . $term . '%') // break the role filter
              ->orWhere('email', 'like', '%' . $term . '%');
        });
    }

    $users         = $query->paginate(20)->withQueryString();
    $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();

    return view('admin.users.index', compact('users', 'studyPrograms'));
}
```

Key points: `withCount('bookings')` for the count column, `teamAccount.picLecturer` eager-loaded
for the "PIC: …" sub-label, admins excluded from the result set, and the search term wrapped in a
**grouped `where`** so the OR clause can't leak past the role filter.

### 3.3 List view (smart edit routing)

**File:** [resources/views/admin/users/index.blade.php](../resources/views/admin/users/index.blade.php)

```blade
{{-- Role badge --}}
<span class="{{ $user->role === 'team' ? 'badge-outline' : 'badge-submitted' }} text-[10px]">
    {{ $user->role === 'team' ? 'Tim' : 'Dosen' }}
</span>

{{-- Action: route to the right edit screen by role --}}
@if ($user->role === 'team' && $user->teamAccount)
    <a href="{{ route('admin.teams.edit', $user->teamAccount) }}" class="btn-ghost btn-sm">Edit Tim</a>
@else
    <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm">Edit</a>
@endif
```

The role filter chips merge into the current query string while dropping null values:

```blade
<a href="{{ route('admin.users.index', array_filter([
        'role'             => $val === 'all' ? null : $val,
        'q'                => request('q'),
        'study_program_id' => request('study_program_id'),
    ])) }}"> {{ $label }} </a>
```

### 3.4 Add / edit lecturer — controller

```php
public function create(): View
{
    $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
    return view('admin.users.create', compact('studyPrograms'));
}

public function store(StoreUserRequest $request): RedirectResponse
{
    $user = DB::transaction(function () use ($request) {
        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            // Lecturers authenticate via the study-program flow (no per-user password),
            // but users.password is NOT NULL — store an unusable random hash.
            'password'         => Hash::make(Str::random(40)),
            'role'             => 'lecturer',
            'study_program_id' => $request->study_program_id,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        AuditLogService::record('user.created', $user, [], [
            'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
            'study_program_id' => $user->study_program_id, 'is_active' => $user->is_active,
        ]);

        return $user;
    });

    return redirect()->route('admin.users.index')
        ->with('success', 'Akun dosen ' . $user->name . ' berhasil dibuat.');
}

public function edit(User $user): View
{
    abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat disunting dari panel ini.');
    $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
    return view('admin.users.edit', compact('user', 'studyPrograms'));
}

public function update(UpdateUserRequest $request, User $user): RedirectResponse
{
    abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat disunting dari panel ini.');

    $oldValues = $user->only(['name', 'email', 'study_program_id', 'is_active']);

    $payload = [
        'name'             => $request->name,
        'email'            => $request->email,
        'study_program_id' => $request->study_program_id,
        'is_active'        => $request->boolean('is_active'),
    ];
    if ($request->filled('password')) {                 // only touch password if provided
        $payload['password'] = Hash::make($request->password);
    }
    $user->update($payload);

    $newValues = $user->only(['name', 'email', 'study_program_id', 'is_active']);
    if ($request->filled('password')) {
        $newValues['password'] = '[changed]';           // never log the real password
    }
    AuditLogService::record('user.updated', $user, $oldValues, $newValues);

    return redirect()->route('admin.users.index')
        ->with('success', 'Data ' . $user->name . ' diperbarui.');
}
```

### 3.5 Add team — controller

**File:** [app/Http/Controllers/Admin/AdminTeamController.php](../app/Http/Controllers/Admin/AdminTeamController.php)

```php
public function store(StoreTeamRequest $request): RedirectResponse
{
    // M5 fix: filter out blank member rows so we never insert empty NOT-NULL fields.
    $members = collect($request->input('members', []))
        ->map(fn ($m) => ['name' => trim($m['name'] ?? ''), 'nim' => trim($m['nim'] ?? '')])
        ->filter(fn ($m) => $m['name'] !== '' && $m['nim'] !== '')
        ->values();

    $team = DB::transaction(function () use ($request, $members) {
        $user = User::create([
            'name'             => $request->team_name,
            'email'            => $request->email,
            'password'         => Hash::make(Str::random(40)),  // unusable hash, as above
            'role'             => 'team',
            'study_program_id' => $request->study_program_id,
            'is_active'        => true,
        ]);

        $team = Team::create([
            'user_id'          => $user->id,
            'pic_lecturer_id'  => $request->pic_user_id,
            'study_program_id' => $request->study_program_id,
            'name'             => $request->team_name,
            'description'      => $request->description,
            'is_active'        => true,
        ]);

        foreach ($members as $m) {
            TeamMember::create([
                'team_id'           => $team->id,
                'student_name'      => $m['name'],
                'student_id_number' => $m['nim'],
            ]);
        }

        AuditLogService::record('team.created', $team, [], [
            'name' => $team->name, 'email' => $user->email,
            'pic_lecturer_id' => $team->pic_lecturer_id,
            'study_program_id' => $team->study_program_id,
            'member_count' => $members->count(),
        ]);

        return $team;
    });

    return redirect()->route('admin.users.index')
        ->with('success', 'Tim ' . $team->name . ' berhasil dibuat.');
}
```

### 3.6 Edit team — controller (member sync)

```php
public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
{
    $members = collect($request->input('members', []))
        ->map(fn ($m) => ['name' => trim($m['name'] ?? ''), 'nim' => trim($m['nim'] ?? '')])
        ->filter(fn ($m) => $m['name'] !== '' && $m['nim'] !== '')
        ->values();

    $oldValues = [ /* name, email, pic, program, is_active, member_count snapshot */ ];

    DB::transaction(function () use ($request, $team, $members) {
        $user = $team->userAccount;
        if ($user) {
            $userPayload = [
                'name'             => $request->team_name,
                'email'            => $request->email,
                'study_program_id' => $request->study_program_id,
                'is_active'        => $request->boolean('is_active', true),
            ];
            if ($request->filled('password')) {
                $userPayload['password'] = Hash::make($request->password);
            }
            $user->update($userPayload);
        }

        $team->update([
            'pic_lecturer_id'  => $request->pic_user_id,
            'study_program_id' => $request->study_program_id,
            'name'             => $request->team_name,
            'description'      => $request->description,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        // Sync members: simplest correct approach — delete and re-insert.
        $team->members()->delete();
        foreach ($members as $m) {
            TeamMember::create([
                'team_id'           => $team->id,
                'student_name'      => $m['name'],
                'student_id_number' => $m['nim'],
            ]);
        }
    });

    AuditLogService::record('team.updated', $team, $oldValues, [ /* new snapshot */ ]);

    return redirect()->route('admin.users.index')
        ->with('success', 'Tim ' . $team->name . ' diperbarui.');
}
```

The team account, the team record, and the member list are all updated inside one
transaction. Members use a **delete-and-re-insert** strategy — the simplest way to make the
stored set exactly match the submitted set.

### 3.7 Validation (Form Requests)

All four requests authorize with `auth()->user()->isAdmin()`.

**`StoreUserRequest`** / **`UpdateUserRequest`** — [app/Http/Requests/Admin/](../app/Http/Requests/Admin/):

```php
// Store
'name'             => ['required', 'string', 'max:255'],
'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
'is_active'        => ['nullable'],

// Update — same, but email ignores the current user and password is optional:
'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
'password' => ['nullable', 'string', 'min:8', 'confirmed'],
```

**`StoreTeamRequest`** / **`UpdateTeamRequest`**:

```php
'team_name'        => ['required', 'string', 'max:255'],
'email'            => ['required', 'email', 'max:255', 'unique:users,email'], // ->ignore($userId) on update
'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
'pic_user_id'      => ['required',
    Rule::exists('users', 'id')->where(fn ($q) =>
        $q->where('role', 'lecturer')->where('is_active', true)), // PIC must be an active lecturer
],
'description'      => ['nullable', 'string', 'max:500'],
'members'          => ['nullable', 'array'],
// A row is only required-complete when one of its two fields is filled:
'members.*.name'   => ['nullable', 'string', 'max:255', 'required_with:members.*.nim'],
'members.*.nim'    => ['nullable', 'string', 'max:50',  'required_with:members.*.name'],
```

The `required_with` pair enforces that a member row must have **both** a name and a NIM (or
neither — fully blank rows are dropped in the controller). Custom Indonesian messages are
defined in each request's `messages()`.

### 3.8 Dynamic member editor (Alpine.js)

Both team forms use an identical Alpine component to add/remove member rows client-side. On
**create** it seeds one empty row (or `old('members')` after a validation error); on **edit**
it seeds the existing members.

**Create** — [resources/views/admin/teams/create.blade.php](../resources/views/admin/teams/create.blade.php):

```blade
@php
    $oldMembers = old('members', [['name' => '', 'nim' => '']]);
    if (empty($oldMembers)) { $oldMembers = [['name' => '', 'nim' => '']]; }
@endphp

<form method="POST" action="{{ route('admin.teams.store') }}"
      x-data="{
          members: {{ \Illuminate\Support\Js::from($oldMembers) }},
          addMember() { this.members.push({ name: '', nim: '' }) },
          removeMember(i) { if (this.members.length > 1) this.members.splice(i, 1) }
      }">
    @csrf
    {{-- … identity fields … --}}

    <template x-for="(member, index) in members" :key="index">
        <div class="flex items-start gap-3">
            <input type="text" :name="'members['+index+'][name]'" x-model="member.name" class="form-input">
            <input type="text" :name="'members['+index+'][nim]'"  x-model="member.nim"  class="form-input font-mono">
            <button type="button" @click="removeMember(index)" :disabled="members.length === 1">✕</button>
        </div>
    </template>

    <button type="button" @click="addMember()">Tambah Anggota</button>
</form>
```

**Edit** — [resources/views/admin/teams/edit.blade.php](../resources/views/admin/teams/edit.blade.php) — differs only in seeding from existing members and adding `@method('PUT')`:

```blade
@php
    $existingMembers = $team->members->map(fn ($m) => [
        'name' => $m->student_name,
        'nim'  => $m->student_id_number,
    ])->toArray();

    $initialMembers = old('members', $existingMembers);
    if (empty($initialMembers)) { $initialMembers = [['name' => '', 'nim' => '']]; }
@endphp
```

The dynamic `:name="'members['+index+'][name]'"` binding produces the `members[i][name]` /
`members[i][nim]` array structure the controller and validation rules expect.

### 3.9 Edit lecturer view (password reset block)

**File:** [resources/views/admin/users/edit.blade.php](../resources/views/admin/users/edit.blade.php)

```blade
<x-section label="Reset Password">
    <p class="text-sm text-ink-700/60 mb-4">Kosongkan jika tidak ingin mengubah password.</p>
    <input type="password" name="password" placeholder="Minimal 8 karakter" class="form-input">
    <input type="password" name="password_confirmation" class="form-input">
</x-section>
```

Leaving both fields empty preserves the existing password (the controller only writes
`password` when `$request->filled('password')`).

---

## 4. Business Rules & Notable Behaviours

| Rule | Where enforced |
|---|---|
| Only admins can reach any of these screens | `admin` middleware + `authorize()` in each Form Request |
| Admin accounts are excluded from the list and cannot be edited | `where('role','!=','admin')` in `index`; `abort_if($user->isAdmin(), 403)` in `edit`/`update` |
| Email must be unique across all users | `unique:users,email` (ignoring self on update) |
| A team's PIC must be an **active lecturer** | `Rule::exists(...)->where(role=lecturer, is_active=true)` |
| Lecturers/teams have no real login password | random 40-char hash stored at create; login is via study-program flow |
| A member row needs both name **and** NIM | `required_with` rules + controller filtering of blank rows |
| Team edits replace the whole member set | `members()->delete()` then re-insert, inside a transaction |
| Account creation/edit is atomic | wrapped in `DB::transaction()` |
| Every create/edit is audited | `AuditLogService::record('user.created' | 'user.updated' | 'team.created' | 'team.updated', …)` |
| Passwords never appear in audit logs | logged as `'[changed]'` |
| No hard delete | accounts are deactivated via `is_active`, preserving history |

---

## 5. Key reference points

| Concern | Location |
|---|---|
| User list / add / edit controller | [app/Http/Controllers/Admin/AdminUserController.php](../app/Http/Controllers/Admin/AdminUserController.php) |
| Team add / edit controller | [app/Http/Controllers/Admin/AdminTeamController.php](../app/Http/Controllers/Admin/AdminTeamController.php) |
| List view | [resources/views/admin/users/index.blade.php](../resources/views/admin/users/index.blade.php) |
| Add lecturer view | [resources/views/admin/users/create.blade.php](../resources/views/admin/users/create.blade.php) |
| Edit lecturer view | [resources/views/admin/users/edit.blade.php](../resources/views/admin/users/edit.blade.php) |
| Add team view | [resources/views/admin/teams/create.blade.php](../resources/views/admin/teams/create.blade.php) |
| Edit team view | [resources/views/admin/teams/edit.blade.php](../resources/views/admin/teams/edit.blade.php) |
| Validation rules | [app/Http/Requests/Admin/](../app/Http/Requests/Admin/) — `StoreUserRequest`, `UpdateUserRequest`, `StoreTeamRequest`, `UpdateTeamRequest` |
| Audit logging | `App\Services\AuditLogService::record()` |
