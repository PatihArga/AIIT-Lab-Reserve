# Plan: Phase 6 — Admin Approval Backend

**Status:** Not started  
**Scope:** 5 new controllers, 5 form-request classes, 1 service helper, route rewiring, 8 admin views updated  
**Risk:** Medium — approving a booking is a write path that must be race-condition safe  
**Phase 5 dependency:** `BookingService::checkConflict()` is reused in the approve action; no schema changes

---

## 1. What Phase 6 Delivers

| Capability | Endpoint |
|---|---|
| Admin dashboard — real counts + pending list | `GET /admin/dashboard` |
| Requests list — real data + server filters | `GET /admin/requests` |
| Request detail — real booking + live conflict check | `GET /admin/requests/{booking}` |
| Approve booking | `POST /admin/requests/{booking}/approve` |
| Reject booking | `POST /admin/requests/{booking}/reject` |
| Mark booking completed | `POST /admin/requests/{booking}/complete` |
| Computer list — real data | `GET /admin/computers` |
| Computer status toggle | `POST /admin/computers/{computer}/status` |
| Users list — real data + filters | `GET /admin/users` |
| Create lecturer account | `POST /admin/users` |
| Edit user (name, email, study program, password, active) | `PUT /admin/users/{user}` |
| Create team + members (2-record atomic) | `POST /admin/teams` |
| Edit team + members | `PUT /admin/teams/{team}` |

**Not in Phase 6:** email notifications, Google Calendar events, reports, audit-log view, settings (Phases 7–8).  
Audit log **writes** ARE included in Phase 6 (approve, reject, computer toggle, user changes). Audit log reads are Phase 8.

---

## 2. Discovered Issues in Current Static Views

These bugs exist in Phase 4 views and must be fixed as part of wiring each view.

| # | File | Issue |
|---|---|---|
| B1 | `admin/users/create.blade.php:14` | `action="{{ route('admin.users.index') }}"` — wrong route (GET route); must be a new `admin.users.store` POST route |
| B2 | `admin/users/edit.blade.php:14` | `action="{{ route('admin.users.index') }}"` — wrong route; must be `admin.users.update` PUT route with user ID |
| B3 | `admin/teams/create.blade.php:14` | `action="{{ route('admin.users.index') }}"` — wrong route; must be `admin.teams.store` POST route |
| B4 | `admin/requests/index.blade.php` | Tab filtering is Alpine.js client-side on hardcoded array — must become server-side GET param |
| B5 | `admin/requests/index.blade.php:91` | `route('admin.requests.show', 1)` — hardcoded ID 1; must use real booking ID |
| B6 | `admin/requests/show.blade.php` | Approve/reject buttons have no `<form>`, no `@csrf`, no action; must be wired to real routes |
| B7 | `admin/requests/show.blade.php` | Conflict check shows hardcoded "Tidak ada konflik" — must call real `checkConflict()` |
| B8 | `admin/requests/show.blade.php` | All booking fields (code, user, type, date, time, logbook) are hardcoded dummy strings |
| B9 | `admin/computers/index.blade.php` | All buttons have no form/action; `$computers` is a hardcoded array |
| B10 | `admin/users/index.blade.php` | `$users` is a hardcoded array; route links use hardcoded `$user['id']` which is fine since key name will stay `id` |
| B11 | `admin/dashboard.blade.php` | All stats (3, 47, 9, 62%) are hardcoded; pending list and activity feed are hardcoded |
| B12 | `admin/users/create.blade.php` | Study program `<option>` values are hardcoded (1=TI, 2=SI, 3=TE); must be real DB rows |
| B13 | `admin/teams/create.blade.php` | Study program options AND PIC lecturer options are hardcoded |

---

## 3. Do Not Touch

- `app/Services/BookingService.php` — reused as-is in the approve action; do not modify
- `app/Models/Booking.php` — all needed columns (`admin_notes`, `reviewed_by`, `reviewed_at`) are already in `$fillable`
- `resources/views/booking/` — user-facing booking views; Phase 6 only touches admin views
- `routes/auth.php` — auth routes are not changing
- User-facing routes in `routes/web.php` under `Route::middleware(['auth','active'])` — not changing
- All Phase 5 controllers (`BookingController`, `BookingLogbookController`, `AvailabilityController`)
- All existing database migrations — no new schema changes needed

---

## 4. Architecture Decisions

### 4.1 Controller Namespace

All admin controllers go under `app/Http/Controllers/Admin/` with namespace `App\Http\Controllers\Admin`.

New files:
- `app/Http/Controllers/Admin/AdminDashboardController.php`
- `app/Http/Controllers/Admin/AdminRequestController.php`
- `app/Http/Controllers/Admin/AdminComputerController.php`
- `app/Http/Controllers/Admin/AdminUserController.php`
- `app/Http/Controllers/Admin/AdminTeamController.php`

### 4.2 Form Request Namespace

All admin form requests go under `app/Http/Requests/Admin/`:
- `ApproveRequestRequest.php` — (empty; action has no user input beyond route model binding)
- `RejectRequestRequest.php` — validates `admin_notes` (required, min 10 chars)
- `StoreUserRequest.php` — validates name, email, study_program_id, password, is_active
- `UpdateUserRequest.php` — same as store but password nullable, email unique ignores current user
- `StoreTeamRequest.php` — validates team_name, email, study_program_id, pic_user_id, password, members[]
- `ComputerStatusRequest.php` — validates status (online|maintenance|offline)

### 4.3 Approve Action — Race Condition Safety

Approving a booking must re-run conflict detection inside a DB transaction with `lockForUpdate()` — the same pattern used in `BookingService::createBooking()`. This prevents a race where two admins approve conflicting bookings simultaneously.

```
POST /admin/requests/{booking}/approve
  → DB::transaction {
      checkConflict(excludeBookingId: $booking->id)
      if conflict → reject with 422, show flash error
      $booking->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])
      AuditLog::create(...)
    }
  → redirect back with success/error flash
```

`BookingService::checkConflict()` already accepts `$excludeBookingId` for this exact purpose.

### 4.4 Reject Action

Rejecting does NOT need a conflict check. The admin provides a reason (`admin_notes`). No transaction needed beyond the normal write guarantee.

```
POST /admin/requests/{booking}/reject
  → validate admin_notes (required, min 10)
  → $booking->update(['status' => 'rejected', 'admin_notes' => ..., 'reviewed_by' => ..., 'reviewed_at' => now()])
  → AuditLog::create(...)
  → redirect to requests index with success flash
```

### 4.5 Mark Completed Action

Admin manually marks an approved booking as completed. No conflict check needed. No reason required. Constraint: booking must currently be `approved`.

```
POST /admin/requests/{booking}/complete
  → abort_if($booking->status !== 'approved', 422)
  → $booking->update(['status' => 'completed'])
  → AuditLog::create(...)
  → redirect back with success flash
```

### 4.6 Computer Status Toggle

Simple status write with an audit log entry. One route, takes `status` in POST body.

Valid transitions:
- `online` → `maintenance`
- `maintenance` → `online`
- `offline` → `online`

Optionally updates `specs_note` if provided in the POST body.

### 4.7 Team Creation — Two-Record Pattern

A Team in this system is modeled as **two coupled records**:
1. A `User` record (role = 'team') — the team's login account  
2. A `Team` record — references the user_id + pic_lecturer_id + members

This must be atomic. Both are written inside a single `DB::transaction()`.

```
DB::transaction {
    $user = User::create([name, email, password, role='team', study_program_id, is_active])
    $team = Team::create([user_id=$user->id, pic_lecturer_id, study_program_id, name, is_active=true])
    foreach members[] → TeamMember::create([team_id=$team->id, student_name, student_id_number])
}
```

If any step fails, the whole transaction rolls back — no orphan user record.

### 4.8 Audit Log Writes

Write to `audit_logs` for these actions:

| Action string | Triggered by |
|---|---|
| `booking.approved` | Admin approves booking |
| `booking.rejected` | Admin rejects booking |
| `booking.completed` | Admin marks booking complete |
| `computer.status_changed` | Computer status toggle |
| `user.created` | Admin creates lecturer |
| `user.updated` | Admin edits user |
| `team.created` | Admin creates team |
| `team.updated` | Admin edits team |

`old_values` and `new_values` should store only the changed fields, not the entire record.

### 4.9 Request Detail Conflict Check

The `show` page currently hardcodes "Tidak ada konflik". In Phase 6, `AdminRequestController::show()` will call `checkConflict()` with `excludeBookingId = $booking->id` and pass `$hasConflict` as a boolean to the view. The view will then show a green "no conflict" or red "ada konflik" box accordingly.

---

## 5. New Files

### 5.1 Controllers

**`app/Http/Controllers/Admin/AdminDashboardController.php`**

Method: `index()` — queries:
- Count of bookings with `status IN (submitted, under_review)` — "Menunggu Tinjauan"
- Count of bookings `status = approved`, `whereMonth(reviewed_at, now())` — "Disetujui Bulan Ini"
- Count of computers `status = online` — "Unit Aktif"
- Pending bookings (submitted + under_review) ordered by `submitted_at`, limit 5 — pending table
- Recent processed bookings (approved + rejected + completed), limit 3 — activity feed
- All computers for the mini grid at the bottom

---

**`app/Http/Controllers/Admin/AdminRequestController.php`**

Methods:
- `index(Request $r)` — paginated list; server-side filters: `status`, `date`, `q` (booking code or user name LIKE)
- `show(Booking $booking)` — load relationships, run `checkConflict()` with excludeBookingId, pass `$hasConflict` to view
- `approve(Booking $booking)` — see §4.3; only bookings with status `submitted` or `under_review` can be approved
- `reject(RejectRequestRequest $r, Booking $booking)` — see §4.4; same status constraint
- `complete(Booking $booking)` — see §4.5

Status constraint for approve/reject: abort 422 if `!in_array($booking->status, ['submitted', 'under_review'])`.

---

**`app/Http/Controllers/Admin/AdminComputerController.php`**

Methods:
- `index()` — returns all computers ordered by unit_number with real data + summary counts
- `updateStatus(ComputerStatusRequest $r, Computer $computer)` — toggles status + optional specs_note

---

**`app/Http/Controllers/Admin/AdminUserController.php`**

Methods:
- `index(Request $r)` — paginated users excluding admin role; filters: role (lecturer/team), study_program_id, q
- `create()` — shows create form with real `$studyPrograms` from DB
- `store(StoreUserRequest $r)` — creates lecturer User record, writes audit log
- `edit(User $user)` — shows edit form with real `$studyPrograms` from DB; abort 403 if $user is admin
- `update(UpdateUserRequest $r, User $user)` — updates name, email, study_program_id, is_active, password (nullable), writes audit log

Note: **only lecturer and team accounts** are managed here. The admin account is not exposed.

---

**`app/Http/Controllers/Admin/AdminTeamController.php`**

Methods:
- `create()` — shows create form with real `$studyPrograms` and `$lecturers` (users with role=lecturer, active)
- `store(StoreTeamRequest $r)` — atomic: create User + Team + TeamMembers; writes audit log
- `edit(Team $team)` — loads team with members, lecturers, study programs
- `update(Request $r, Team $team)` — updates team identity + syncs members (delete old, insert new)

---

### 5.2 Form Request Classes

**`app/Http/Requests/Admin/RejectRequestRequest.php`**
```php
rules: [
    'admin_notes' => ['required', 'string', 'min:10', 'max:2000'],
]
messages: [
    'admin_notes.required' => 'Alasan penolakan wajib diisi.',
    'admin_notes.min'      => 'Alasan minimal 10 karakter.',
]
```

**`app/Http/Requests/Admin/StoreUserRequest.php`**
```php
rules: [
    'name'             => ['required', 'string', 'max:255'],
    'email'            => ['required', 'email', 'unique:users,email'],
    'study_program_id' => ['required', 'exists:study_programs,id'],
    'password'         => ['required', 'string', 'min:8', 'confirmed'],
    'is_active'        => ['nullable'],
]
```

**`app/Http/Requests/Admin/UpdateUserRequest.php`**
```php
rules: [
    'name'             => ['required', 'string', 'max:255'],
    'email'            => ['required', 'email', Rule::unique('users','email')->ignore($this->route('user'))],
    'study_program_id' => ['required', 'exists:study_programs,id'],
    'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
    'is_active'        => ['nullable'],
]
```

**`app/Http/Requests/Admin/StoreTeamRequest.php`**
```php
rules: [
    'team_name'        => ['required', 'string', 'max:255'],
    'email'            => ['required', 'email', 'unique:users,email'],
    'study_program_id' => ['required', 'exists:study_programs,id'],
    'pic_user_id'      => ['required', 'exists:users,id'],
    'password'         => ['required', 'string', 'min:8', 'confirmed'],
    'members'          => ['nullable', 'array'],
    'members.*.name'   => ['nullable', 'string', 'max:255'],
    'members.*.nim'    => ['nullable', 'string', 'max:50'],
]
```

**`app/Http/Requests/Admin/ComputerStatusRequest.php`**
```php
rules: [
    'status'     => ['required', Rule::in(['online', 'maintenance', 'offline'])],
    'specs_note' => ['nullable', 'string', 'max:500'],
]
```

---

## 6. Route Changes

Replace all closure stubs in `routes/web.php` under `Route::middleware('admin')` with controller references.

### Current (Phase 4 — all closures)
```php
Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
Route::get('/requests', fn() => view('admin.requests.index'))->name('requests.index');
Route::get('/requests/{id}', fn($id) => view('admin.requests.show', ['id' => $id]))->name('requests.show');
Route::get('/computers', fn() => view('admin.computers.index'))->name('computers.index');
Route::get('/users', fn() => view('admin.users.index'))->name('users.index');
Route::get('/users/create', fn() => view('admin.users.create'))->name('users.create');
Route::get('/users/{id}/edit', fn($id) => view('admin.users.edit', ['id' => $id]))->name('users.edit');
Route::get('/teams/create', fn() => view('admin.teams.create'))->name('teams.create');
// ... reports, audit-log, settings remain as closures (Phase 8)
```

### After Phase 6

```php
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminComputerController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminTeamController;

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Requests
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{booking}', [AdminRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{booking}/approve', [AdminRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{booking}/reject', [AdminRequestController::class, 'reject'])->name('requests.reject');
    Route::post('/requests/{booking}/complete', [AdminRequestController::class, 'complete'])->name('requests.complete');

    // Computers
    Route::get('/computers', [AdminComputerController::class, 'index'])->name('computers.index');
    Route::post('/computers/{computer}/status', [AdminComputerController::class, 'updateStatus'])->name('computers.status');

    // Users (lecturers + team accounts)
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    // Teams
    Route::get('/teams/create', [AdminTeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [AdminTeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}/edit', [AdminTeamController::class, 'edit'])->name('teams.edit');
    Route::put('/teams/{team}', [AdminTeamController::class, 'update'])->name('teams.update');

    // Phase 8 (still closures for now)
    Route::get('/reports', fn() => view('admin.reports.index'))->name('reports.index');
    Route::get('/audit-log', fn() => view('admin.audit-log.index'))->name('audit-log.index');
    Route::get('/settings', fn() => view('admin.settings.index'))->name('settings.index');
});
```

---

## 7. View Changes per File

### `admin/dashboard.blade.php`
- Remove `@php $pending = [...]` and `@php $recent = [...]` hardcoded arrays
- Replace hardcoded stat numbers with `$stats['pending_count']`, `$stats['approved_this_month']`, `$stats['computers_online']`
- Replace pending table rows with real `@foreach ($pendingBookings as $b)` using model attributes
- Replace hardcoded activity feed with `@foreach ($recentActivity as $a)` (last 4 audit log entries, or last 4 processed bookings if audit log not available — use the processed bookings approach since audit log reads are Phase 8)
- Replace `$dummyComputers` with `$computers` (real Computer collection)
- Update the "Tinjau N Permintaan" button count to use `$stats['pending_count']`
- Update the lab status strip (unit online count, maintenance count) to use real `$computers`

### `admin/requests/index.blade.php`
- Remove `@php $requests = [...]` hardcoded array
- Add `<form method="GET">` wrapping the filter inputs (tabs become link-based with `?status=...`)
- Status tabs: convert Alpine `x-data` tab to `<a href="{{ route('admin.requests.index') }}?status=...">` links styled to match current query param
- Search input, category select, date input: wrap in GET form submission
- Table rows: real `@foreach ($bookings as $b)` with `$b->booking_code`, `$b->user->name`, etc.
- The "Tinjau" button: use real `route('admin.requests.show', $b)` with the Booking model (route model binding)
- Add paginator at the bottom
- Pending count chip: use `$pendingCount` passed from controller

### `admin/requests/show.blade.php`
- Remove all hardcoded values; use `$booking` model passed from controller
- Compute booking type label, date, duration, day name in PHP or with a helper
- Replace hardcoded logbook fields with `$booking->logbook->category`, `->checkpoint_progress`, etc.
- Computer grid: use `$booking->computers` for `computers_only` type, or a label like "Seluruh Lab" for `full_room`
- **Fix B6:** Add `<form method="POST" action="{{ route('admin.requests.approve', $booking) }}">@csrf` around the approve button
- **Fix B7:** Replace hardcoded conflict check with real `$hasConflict` boolean; show red banner if conflict detected
- Reject form: add proper `method="POST" action="{{ route('admin.requests.reject', $booking) }}"` with the reason textarea wired to `name="admin_notes"`
- Add "Tandai Selesai" section visible only when `$booking->status === 'approved'`
- Show approve/reject panels only when `$booking->status` is `submitted` or `under_review`; show read-only review info when already processed

### `admin/computers/index.blade.php`
- Remove `@php $computers = collect([...])` hardcoded array
- Use real `$computers` passed from controller
- Summary chips (Online / Pemeliharaan / Nonaktif counts): compute from `$computers->groupBy('status')`
- Status toggle buttons: wrap each in its own `<form method="POST">` targeting `route('admin.computers.status', $pc)` with `@csrf` and a hidden `<input name="status" value="...">` for the target state
- "Edit Catatan" Alpine.js expand: keep the Alpine toggle, but wire the textarea name to `specs_note`; submit via the same status form or a dedicated specs form
- PC card background/border classes: keep existing `$cardBorder`/`$cardBg` logic but based on `$pc->status` (model property, not `$pc['status']`)
- specs display: `$pc->specs_note` instead of `$pc['specs']`

### `admin/users/index.blade.php`
- Remove `@php $users = [...]` hardcoded array
- Use real `$users` (paginated) from controller
- Filters: role tab, study program select, search input → wrap in `<form method="GET">` and submit on change/enter
- Role tab: convert Alpine `x-data` to GET param links
- Table rows: use model properties (`$user->name`, `$user->email`, `$user->role`, `$user->studyProgram->name`, `$user->bookings_count`, `$user->is_active`)
- Use `withCount('bookings')` in query for the count column
- For team rows: show `$user->teamAccount->picLecturer->name` as PIC
- Edit link: `route('admin.users.edit', $user)` (route model binding, not `$user['id']`)
- Add paginator

### `admin/users/create.blade.php`
- **Fix B1:** Change `action` to `{{ route('admin.users.store') }}` (new POST route)
- Replace hardcoded study program options with `@foreach ($studyPrograms as $sp) <option value="{{ $sp->id }}">{{ $sp->name }}</option>`
- Add error display: `@error('name')`, `@error('email')`, etc.
- Preserve old input with `value="{{ old('name') }}"` etc.

### `admin/users/edit.blade.php`
- **Fix B2:** Change `action` to `{{ route('admin.users.update', $user) }}`
- Replace hardcoded values with `value="{{ old('name', $user->name) }}"` etc.
- Replace hardcoded study program options with real DB data, selected option matches `$user->study_program_id`
- is_active checkbox: `checked="{{ old('is_active', $user->is_active) ? 'checked' : '' }}"`

### `admin/teams/create.blade.php`
- **Fix B3:** Change `action` to `{{ route('admin.teams.store') }}`
- Replace hardcoded study program options with real DB data
- Replace hardcoded PIC options with real lecturer list from DB
- Keep Alpine.js dynamic member rows (already correct)
- Add error display

---

## 8. Implementation Steps

### Step 1 — Routes (wire all admin routes)

**File:** `routes/web.php`

Add 5 `use` imports for the new Admin controllers. Replace all closure stubs for dashboard, requests, computers, users, and teams with real controller references. Add new POST routes (approve, reject, complete, status toggle, users.store, users.update, teams.store, teams.update). Leave reports/audit-log/settings as closures.

---

### Step 2 — `AdminDashboardController`

**File:** `app/Http/Controllers/Admin/AdminDashboardController.php`

```php
public function index(): View
{
    $stats = [
        'pending_count'       => Booking::whereIn('status', ['submitted', 'under_review'])->count(),
        'approved_this_month' => Booking::where('status', 'approved')
            ->whereMonth('reviewed_at', now()->month)->whereYear('reviewed_at', now()->year)->count(),
        'computers_online'    => Computer::where('status', 'online')->count(),
        'computers_total'     => Computer::count(),
    ];

    $pendingBookings = Booking::with(['user', 'user.teamAccount'])
        ->whereIn('status', ['submitted', 'under_review'])
        ->orderBy('submitted_at')->limit(5)->get();

    $recentActivity = Booking::with('user')
        ->whereIn('status', ['approved', 'rejected', 'completed'])
        ->latest('reviewed_at')->limit(4)->get();

    $computers = Computer::orderBy('unit_number')->get();

    return view('admin.dashboard', compact('stats', 'pendingBookings', 'recentActivity', 'computers'));
}
```

---

### Step 3 — `AdminRequestController`

**File:** `app/Http/Controllers/Admin/AdminRequestController.php`

```php
// index() — server-side filters
public function index(Request $request): View
{
    $query = Booking::with(['user', 'user.teamAccount', 'logbook'])
        ->latest('submitted_at');

    if ($request->filled('status') && $request->status !== 'all') {
        $query->where('status', $request->status);
    }
    if ($request->filled('date')) {
        $query->whereDate('date', $request->date);
    }
    if ($request->filled('q')) {
        $query->where(function ($q) use ($request) {
            $q->where('booking_code', 'like', '%' . $request->q . '%')
              ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $request->q . '%'));
        });
    }

    $bookings     = $query->paginate(20)->withQueryString();
    $pendingCount = Booking::whereIn('status', ['submitted', 'under_review'])->count();

    return view('admin.requests.index', compact('bookings', 'pendingCount'));
}

// show() — with real conflict check
public function show(Booking $booking): View
{
    $booking->load(['user', 'user.teamAccount', 'computers', 'logbook', 'reviewer']);

    $hasConflict = DB::transaction(fn () => $this->bookings->checkConflict(
        date:            $booking->date->format('Y-m-d'),
        startTime:       substr($booking->start_time, 0, 5),
        endTime:         substr($booking->end_time, 0, 5),
        bookingType:     $booking->booking_type,
        computerIds:     $booking->computers->pluck('id')->toArray(),
        roomSharing:     $booking->room_sharing,
        excludeBookingId: $booking->id,
    ));

    return view('admin.requests.show', compact('booking', 'hasConflict'));
}

// approve() — race-condition safe
public function approve(Booking $booking): RedirectResponse
{
    abort_if(! in_array($booking->status, ['submitted', 'under_review']), 422,
        'Permintaan ini sudah diproses.');

    try {
        DB::transaction(function () use ($booking) {
            $conflict = $this->bookings->checkConflict(
                date:            $booking->date->format('Y-m-d'),
                startTime:       substr($booking->start_time, 0, 5),
                endTime:         substr($booking->end_time, 0, 5),
                bookingType:     $booking->booking_type,
                computerIds:     $booking->computers->pluck('id')->toArray(),
                roomSharing:     $booking->room_sharing,
                excludeBookingId: $booking->id,
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'Slot ini sekarang bentrok dengan reservasi lain. Persetujuan dibatalkan.'
                );
            }

            $booking->update([
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_notes' => null,
            ]);

            AuditLog::create([
                'user_id'        => auth()->id(),
                'action'         => 'booking.approved',
                'auditable_type' => Booking::class,
                'auditable_id'   => $booking->id,
                'old_values'     => ['status' => 'submitted'],
                'new_values'     => ['status' => 'approved'],
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
            ]);
        });
    } catch (BookingConflictException $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('admin.requests.index')
        ->with('success', 'Reservasi ' . $booking->booking_code . ' telah disetujui.');
}

// reject()
public function reject(RejectRequestRequest $request, Booking $booking): RedirectResponse
{
    abort_if(! in_array($booking->status, ['submitted', 'under_review']), 422,
        'Permintaan ini sudah diproses.');

    $booking->update([
        'status'      => 'rejected',
        'admin_notes' => $request->admin_notes,
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
    ]);

    AuditLog::create([...]);

    return redirect()->route('admin.requests.index')
        ->with('success', 'Reservasi ' . $booking->booking_code . ' telah ditolak.');
}

// complete()
public function complete(Booking $booking): RedirectResponse
{
    abort_if($booking->status !== 'approved', 422,
        'Hanya reservasi yang disetujui yang dapat ditandai selesai.');

    $booking->update(['status' => 'completed']);
    AuditLog::create([...]);

    return back()->with('success', 'Reservasi ditandai selesai.');
}
```

The controller constructor injects `BookingService $bookings` (same pattern as Phase 5's `BookingController`).

---

### Step 4 — `AdminComputerController`

**File:** `app/Http/Controllers/Admin/AdminComputerController.php`

```php
public function index(): View
{
    $computers = Computer::orderBy('unit_number')->get();
    $counts = [
        'online'      => $computers->where('status', 'online')->count(),
        'maintenance' => $computers->where('status', 'maintenance')->count(),
        'offline'     => $computers->where('status', 'offline')->count(),
    ];
    return view('admin.computers.index', compact('computers', 'counts'));
}

public function updateStatus(ComputerStatusRequest $request, Computer $computer): RedirectResponse
{
    $oldStatus = $computer->status;
    $computer->update([
        'status'     => $request->status,
        'specs_note' => $request->filled('specs_note') ? $request->specs_note : $computer->specs_note,
    ]);

    AuditLog::create([
        'user_id'        => auth()->id(),
        'action'         => 'computer.status_changed',
        'auditable_type' => Computer::class,
        'auditable_id'   => $computer->id,
        'old_values'     => ['status' => $oldStatus],
        'new_values'     => ['status' => $request->status],
        'ip_address'     => request()->ip(),
        'user_agent'     => request()->userAgent(),
    ]);

    return back()->with('success', $computer->label . ' diperbarui ke ' . $request->status . '.');
}
```

---

### Step 5 — `AdminUserController`

**File:** `app/Http/Controllers/Admin/AdminUserController.php`

Key details:
- `index()`: eager-load `studyProgram`, `teamAccount.picLecturer`, and `withCount('bookings')`; filter by `role` and `study_program_id` and search `q`
- `create()`: pass `$studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get()`
- `store()`: create User with `role = 'lecturer'`; write audit log
- `edit()`: abort 403 if `$user->isAdmin()` (do not expose admin account for editing)
- `update()`: update name, email, study_program_id, is_active; if `$request->filled('password')`, update password; write audit log

---

### Step 6 — `AdminTeamController`

**File:** `app/Http/Controllers/Admin/AdminTeamController.php`

Key details:
- `create()`: pass `$studyPrograms` + `$lecturers = User::where('role', 'lecturer')->where('is_active', true)->orderBy('name')->get()`
- `store()`: atomic DB::transaction — create User (role='team') + Team + TeamMembers; filter out blank member rows
- `edit(Team $team)`: load `$team->members`, `$studyPrograms`, `$lecturers`; pass `$teamUser = $team->userAccount`
- `update()`: update both `users` and `teams` records; sync members by deleting old rows and re-inserting

---

### Step 7 — Update `admin/dashboard.blade.php`

Remove dummy PHP data. Wire:
- `$stats['pending_count']` → pending count chip and CTA button
- `$stats['approved_this_month']` → "Disetujui Bulan Ini"
- `$stats['computers_online']` / `$stats['computers_total']` → "Unit Aktif"
- `$pendingBookings` → pending table (loop with `$b->booking_code`, `$b->user->name`, etc.)
- `$recentActivity` → activity feed (use booking status changes as feed items)
- `$computers` → real computer grid at bottom

Routing links on pending rows: `route('admin.requests.show', $b)`.

---

### Step 8 — Update `admin/requests/index.blade.php`

- Replace Alpine tab-filtering with GET-param-based server filtering
- Status tabs become `<a href="{{ route('admin.requests.index', ['status' => $val]) }}">` with active class based on `request('status') === $val`
- Filter inputs become `<form method="GET">` with `<input name="status" type="hidden" ...>` to preserve status, plus `name="q"` and `name="date"`
- Loop: `@foreach ($bookings as $b)`
- Routing: `route('admin.requests.show', $b)`
- Paginator: `{{ $bookings->withQueryString()->links() }}`

---

### Step 9 — Update `admin/requests/show.blade.php`

The most significant view rewrite. Key sections:

**Booking summary card:** Use `$booking->booking_code`, `$booking->status`, `$booking->user->name`, booking type label (PHP match), `$booking->date->translatedFormat('d F Y')`, `$booking->start_time` / `$booking->end_time`, duration computed with Carbon diff, `$booking->submitted_at->translatedFormat(...)`.

**Computer grid section:** Conditionally show only for `computers_only` or `full_room`. For `computers_only`, pass `$booking->computers`. For `full_room`, display all 9 computers read-only with a "Seluruh Lab" label.

**Logbook section:** Use `$booking->logbook->category`, `->checkpoint_progress`, `->related_course`, `->supervisor_name`, `->needs_internet`, etc. Show "Belum diisi" if `$booking->logbook === null`.

**Conflict check panel:**
```blade
@if ($hasConflict)
    {{-- Red warning banner --}}
@else
    {{-- Green "no conflict" banner --}}
@endif
```

**Approve panel:** Visible only when `in_array($booking->status, ['submitted', 'under_review'])`:
```html
<form method="POST" action="{{ route('admin.requests.approve', $booking) }}">
    @csrf
    <button type="submit" class="w-full btn-mark ...">Setujui Permintaan</button>
</form>
```

**Reject panel:**
```html
<form method="POST" action="{{ route('admin.requests.reject', $booking) }}">
    @csrf
    <textarea name="admin_notes" required minlength="10">{{ old('admin_notes') }}</textarea>
    <button type="submit" class="w-full btn-danger ...">Konfirmasi Penolakan</button>
</form>
```

**Mark Completed panel:** Visible only when `$booking->status === 'approved'`.

**Already-reviewed panel:** When status is `rejected`/`approved`/`completed`, show reviewer name, timestamp, and `admin_notes` (rejection reason). No action buttons.

---

### Step 10 — Update `admin/computers/index.blade.php`

- Replace `$computers` hardcoded collect() with real `$computers` from controller
- Replace `$pc['status']` → `$pc->status`, `$pc['label']` → `$pc->label`, `$pc['specs']` → `$pc->specs_note`
- Summary chips: use `$counts['online']`, `$counts['maintenance']`, `$counts['offline']`
- Status toggle buttons: wrap in `<form method="POST" action="{{ route('admin.computers.status', $pc) }}">@csrf`:
  - online → `<input name="status" value="maintenance" type="hidden">` + "Tandai Pemeliharaan" button
  - maintenance → `<input name="status" value="online" type="hidden">` + "Selesai, Online" button
  - offline → `<input name="status" value="online" type="hidden">` + "Aktifkan" button
- "Edit Catatan" Alpine toggle: keep the existing x-data toggle; wire the specs_note textarea inside a separate small form targeting the same `admin.computers.status` route with hidden `name="status" value="{{ $pc->status }}"` to preserve current status while updating notes

---

### Step 11 — Update `admin/users/index.blade.php`, `create.blade.php`, `edit.blade.php`, `teams/create.blade.php`

**index:** real data, GET-param filters, paginator, correct route for edit link  
**create:** fix action route to `admin.users.store`, real study programs, old() input preservation, `@error` display  
**edit:** fix action route to `admin.users.update`, prefill from `$user`, real study programs, conditional selected, old() fallback  
**teams/create:** fix action to `admin.teams.store`, real study programs, real lecturers; also add `admin.teams.edit` view (new file, similar structure to create but with prefilled data)

---

## 9. Edge Cases and Guards

| Scenario | Guard |
|---|---|
| Admin tries to approve already-approved booking | `abort_if(!in_array($booking->status, ['submitted','under_review']), 422)` |
| Concurrent admin race on same booking | DB transaction + checkConflict with lockForUpdate re-runs inside approve |
| Admin rejects with blank reason | `RejectRequestRequest` validates `admin_notes` required min:10 |
| Admin tries to deactivate themselves | Not possible — UserController excludes admin role from listing |
| Creating duplicate email for team | `StoreTeamRequest` validates `unique:users,email` |
| Team creation fails on member insert | DB::transaction rolls back User + Team records |
| Computer toggle on computer used by active booking | Allowed — existing booking remains valid; future bookings cannot select offline/maintenance computers (handled by AvailabilityController) |
| Updating email on user/edit clashes with another account | `UpdateUserRequest` uses `Rule::unique()->ignore($user->id)` |
| Show page for booking with no logbook | `$booking->logbook` is nullable — view checks `@if($booking->logbook)` |
| Show page for booking with no computers (room_only) | `$booking->computers` returns empty collection — computer grid section is hidden with `@if($booking->booking_type !== 'room_only')` |
| Admin tries to mark complete a rejected booking | `abort_if($booking->status !== 'approved', 422)` |

---

## 10. Files Changed Summary

### New files (create from scratch)
- `app/Http/Controllers/Admin/AdminDashboardController.php`
- `app/Http/Controllers/Admin/AdminRequestController.php`
- `app/Http/Controllers/Admin/AdminComputerController.php`
- `app/Http/Controllers/Admin/AdminUserController.php`
- `app/Http/Controllers/Admin/AdminTeamController.php`
- `app/Http/Requests/Admin/RejectRequestRequest.php`
- `app/Http/Requests/Admin/StoreUserRequest.php`
- `app/Http/Requests/Admin/UpdateUserRequest.php`
- `app/Http/Requests/Admin/StoreTeamRequest.php`
- `app/Http/Requests/Admin/ComputerStatusRequest.php`
- `resources/views/admin/teams/edit.blade.php` (new — teams/create.blade.php currently has no edit view)

### Modified files
- `routes/web.php` — admin closure routes replaced with controller references + new POST routes added
- `resources/views/admin/dashboard.blade.php` — real data wired
- `resources/views/admin/requests/index.blade.php` — real data + GET filter form
- `resources/views/admin/requests/show.blade.php` — real data + approve/reject forms
- `resources/views/admin/computers/index.blade.php` — real data + status forms
- `resources/views/admin/users/index.blade.php` — real data + GET filter form
- `resources/views/admin/users/create.blade.php` — fix action route + real study programs + error display
- `resources/views/admin/users/edit.blade.php` — fix action route + real data + real study programs
- `resources/views/admin/teams/create.blade.php` — fix action route + real data

### Unchanged files
- All Phase 5 controllers and services
- All user-facing booking views
- All database migrations and models
- `routes/auth.php`

---

## 11. Success Criteria

1. Navigating to `/admin/dashboard` shows real pending count, real computer status, real pending requests list.
2. `/admin/requests` shows real bookings from DB, filters by status/date/search actually filter results, pagination works.
3. `/admin/requests/{id}` shows real booking data (all fields from DB), conflict check is live, approve and reject buttons are functional forms.
4. Approving a booking changes its status to `approved` in DB; attempting to approve a now-conflicting booking shows an error flash without changing status.
5. Rejecting a booking requires and saves `admin_notes`; booking status changes to `rejected`.
6. `/admin/computers` shows real computer statuses; clicking "Tandai Pemeliharaan" on an online PC toggles it to maintenance and vice versa.
7. Creating a lecturer account via `/admin/users/create` creates a real `users` row, validates uniqueness of email, shows validation errors on failure.
8. Editing a user persists changes; leaving password blank does not change existing password.
9. Creating a team creates one `users` row (role=team) AND one `teams` row AND member rows — all or nothing.
10. All approve/reject/toggle actions write a row to `audit_logs`.
11. No admin route is accessible without the `admin` middleware (non-admin users get 403).
