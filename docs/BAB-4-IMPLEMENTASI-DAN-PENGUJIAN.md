# BAB 4 — IMPLEMENTASI DAN PENGUJIAN

## 4.1.3 Implementasi Antarmuka Pengguna

Bagian ini mendokumentasikan implementasi setiap antarmuka pengguna utama pada sistem AIIT Lab Reserve beserta potongan kode yang mengimplementasikannya.

---

### 1. Login Dua Tahap dan Pemilihan Entitas (KF-01)

#### Deskripsi

Sistem menerapkan autentikasi dua tahap. Pada **Tahap 1**, pengguna memasukkan alamat Gmail program studi dan kata sandi bersama (shared credential). Sistem memverifikasi email terhadap tabel `study_programs`. Pada **Tahap 2**, pengguna memilih akun personal (Dosen atau Tim Mahasiswa) dari dropdown yang terfilter berdasarkan program studi yang telah diverifikasi.

Mekanisme ini memisahkan autentikasi institusi (Tahap 1) dari identifikasi individu (Tahap 2), sehingga tidak memerlukan pengelolaan kata sandi per pengguna.

#### Tampilan Tahap 1 — Email Program Studi

File: `resources/views/auth/login.blade.php`

```php
<x-auth-layout title="Masuk">

    {{-- Step indicator --}}
    <div class="flex items-center gap-3 mb-10">
        <div class="step-dot-active">1</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-900">
            Gmail Program Studi
        </span>
        <div class="step-connector"></div>
        <div class="step-dot-pending">2</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-700/40">
            Pilih Akun
        </span>
    </div>

    <h2 class="font-display text-3xl font-bold text-ink-900 tracking-tight">
        Selamat datang.
    </h2>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
        @csrf
        <div class="form-field">
            <label for="email" class="form-label">Gmail Program Studi</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   placeholder="nama.prodi@gmail.com"
                   required autofocus class="form-input" />
        </div>

        <div class="form-field" x-data="{ show: false }">
            <label for="password" class="form-label">Kata Sandi Program Studi</label>
            <div class="relative">
                <input id="password" name="password"
                       :type="show ? 'text' : 'password'"
                       required class="form-input pr-12"
                       placeholder="••••••••" />
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center px-4
                               text-ink-700/50 hover:text-ink-700">
                    <!-- Toggle eye icon -->
                </button>
            </div>
        </div>

        <button type="submit" class="btn-mark btn-lg w-full">
            Lanjutkan →
        </button>
    </form>

    <!-- Link ke login admin -->
    <a href="{{ route('admin.login') }}" class="...">
        Masuk sebagai Administrator
    </a>
</x-auth-layout>
```

#### Tampilan Tahap 2 — Pilih Akun

File: `resources/views/auth/select-user.blade.php`

```php
<x-auth-layout title="Pilih Akun">

    {{-- Step indicator: step 1 complete, step 2 active --}}
    <div class="flex items-center gap-3 mb-10">
        <div class="step-dot-complete">✓</div>
        <span class="...">Gmail Program Studi</span>
        <div class="step-connector-complete"></div>
        <div class="step-dot-active">2</div>
        <span class="...">Pilih Akun</span>
    </div>

    <div class="page-eyebrow">{{ $program->name }}</div>
    <h2 class="font-display text-3xl font-bold text-ink-900">Pilih akun Anda.</h2>

    <form method="POST" action="{{ route('login.authenticate') }}" class="mt-8 space-y-6">
        @csrf
        <div class="form-field">
            <label for="user_id" class="form-label">Nama</label>
            <select id="user_id" name="user_id" required class="form-select">
                <option value="" disabled selected>— Pilih nama Anda —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }}
                        @if ($user->role === 'team') · Tim
                        @else · Dosen
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-mark btn-lg w-full">
            Masuk
        </button>
        <a href="{{ route('login') }}">← Gunakan gmail lain</a>
    </form>
</x-auth-layout>
```

#### Logika Controller — Deteksi Program Studi dan Autentikasi

File: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

```php
class AuthenticatedSessionController extends Controller
{
    /**
     * Step 1 POST: Detect study program from email.
     */
    public function detectStudyProgram(LoginEmailRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->input('email')));
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

        // Verify the shared program password
        if (! Hash::check($request->input('password'), $program->password)) {
            RateLimiter::hit($this->throttleKeyByEmail($request));
            throw ValidationException::withMessages([
                'password' => 'Kata sandi program studi tidak cocok.',
            ]);
        }

        $request->session()->put('login.study_program_id', $program->id);
        return redirect()->route('login.select');
    }

    /**
     * Step 2 POST: Authenticate the selected user.
     */
    public function authenticate(LoginAuthenticateRequest $request): RedirectResponse
    {
        $programId = $request->session()->get('login.study_program_id');

        $user = User::where('id', $request->input('user_id'))
            ->where('study_program_id', $programId)
            ->where('is_active', true)
            ->where('role', '!=', 'admin')
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'user_id' => 'Pengguna yang dipilih tidak valid.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPathByRole($user->role));
    }
}
```

**Fitur keamanan:**
- Rate limiting 5 percobaan per 60 detik per kombinasi email + IP
- Kata sandi program studi disimpan sebagai hash bcrypt
- Sesi diregenerasi setelah login berhasil untuk mencegah session fixation

---

### 2. Kalender Jadwal Laboratorium (KF-10)

#### Deskripsi

Kalender interaktif menampilkan tampilan mingguan (*week-view*) dengan jendela 5 minggu (1 minggu lalu + minggu berjalan + 3 minggu mendatang). Setiap reservasi ditampilkan sebagai blok berwarna sesuai tipe:

| Tipe | Warna | Kode |
|---|---|---|
| Komputer Saja | Indigo | `#4F46E5` |
| Ruang + Komputer | Violet | `#7C3AED` |
| Ruang Eksklusif | Teal | `#0D9488` |
| Ruang Berbagi | Amber | `#D97706` |

#### Controller — Memuat Event Kalender

File: `app/Http/Controllers/CalendarController.php`

```php
class CalendarController extends Controller
{
    public function index(): View
    {
        $calStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks(1);
        $calEnd   = $calStart->copy()->addWeeks(5)->endOfWeek(Carbon::SUNDAY);

        $calendarEvents = Booking::with(['user:id,name', 'computers:id,label,unit_number'])
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->whereBetween('date', [$calStart->toDateString(), $calEnd->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->map(fn (Booking $b) => $this->toCalEvent($b))
            ->values()
            ->toArray();

        return view('calendar.index', [
            'calendarEvents' => $calendarEvents,
            'todayIso'       => now()->toDateString(),
            'loadStartIso'   => $calStart->toDateString(),
            'loadEndIso'     => $calEnd->toDateString(),
        ]);
    }

    private function toCalEvent(Booking $b): array
    {
        $startMin = $this->timeToMin($b->start_time);
        $endMin   = $this->timeToMin($b->end_time);

        if ($b->booking_type === 'computers_only') {
            $type  = 'computer';
            $label = $b->computers->count() === 1
                ? $b->computers->first()->label
                : $b->computers->count() . ' unit';
        } elseif ($b->booking_type === 'full_room') {
            $type  = 'room_computer';
            $label = 'Seluruh Lab';
        } elseif ($b->room_sharing === 'exclusive') {
            $type  = 'room_exclusive';
            $label = 'Ruang (Eksklusif)';
        } else {
            $type  = 'room_sharing';
            $label = 'Ruang (Berbagi)';
        }

        return [
            'id' => $b->id, 'date' => $b->date->format('Y-m-d'),
            'start' => $startMin, 'dur' => max($endMin - $startMin, 30),
            'type' => $type, 'label' => $label,
            'who' => $b->user->name ?? '—', 'status' => $b->status,
            'booking_code' => $b->booking_code,
            'is_mine' => $b->user_id === auth()->id(),
        ];
    }
}
```

#### View — Komponen Alpine.js `weekCal()`

File: `resources/views/calendar/index.blade.php`

Kalender diimplementasikan sebagai komponen Alpine.js dengan fitur:

```javascript
function weekCal() {
    return {
        events: CAL_EVENTS,        // data dari controller (JSON)
        anchor: startOfDay(new Date(TODAY_ISO + 'T00:00:00')),
        view: 'week',              // 'week' atau 'day'
        bookingMode: false,        // mode drag-to-book

        // Computed: generate day columns (Mon-Sat)
        get days() {
            const out = [];
            if (this.view === 'day') {
                out.push(this.mkDay(this.anchor));
            } else {
                const mon = mondayOf(this.anchor);
                for (let i = 0; i < 6; i++)
                    out.push(this.mkDay(addDays(mon, i)));
            }
            return out;
        },

        // Layout engine: position overlapping events in concurrent columns
        layoutDay(dayKey) {
            const evs = this.events.filter(e => e.date === dayKey);
            const sorted = [...evs].sort((a, b) =>
                a.start - b.start || b.dur - a.dur);
            // Greedy column assignment for overlapping events...
        },

        // Slot restriction detection for booking banners
        slotRestrictions(dateKey, start, end) {
            const ov = this.events.filter(e =>
                e.date === dateKey && e.start < end && (e.start + e.dur) > start);
            let hard = false, shared = false, comp = false;
            for (const e of ov) {
                if (e.type === 'room_computer' || e.type === 'room_exclusive')
                    hard = true;
                if (e.type === 'room_sharing') shared = true;
                if (e.type === 'computer') comp = true;
            }
            return { hardBlocked: hard, sharedRoom: shared, computerBooked: comp };
        },
    };
}
```

---

### 3. Formulir Pengajuan Peminjaman Bertahap — 3 Jenis Booking (KF-03)

#### Deskripsi

Formulir reservasi diakses melalui mode *drag-to-book* pada kalender. Pengguna menyeret slot waktu untuk memunculkan popover form yang mengakomodasi tiga jenis peminjaman:

1. **Komputer Saja** (`computers_only`) — pilih 1 unit dari dropdown
2. **Ruang + Komputer** (`full_room`) — pilih 1 atau lebih unit dari grid checkbox
3. **Ruang Saja** (`room_only`) — pilih mode Eksklusif atau Berbagi

#### View — Popover Form Reservasi

File: `resources/views/calendar/index.blade.php`

```html
<!-- Create popover -->
<template x-if="creating">
    <div>
        <div class="wcal-overlay" @mousedown="creating = null"></div>
        <div class="wcal-pop wide" :style="popStyle(creating.pos)">
            <h3>Reservasi Baru</h3>

            <!-- Tanggal (auto-filled dari drag) -->
            <label class="wcal-lbl">Tanggal</label>
            <div class="wcal-datefield">
                <svg><!-- calendar icon --></svg>
                <span x-text="creating.dateLabel"></span>
            </div>

            <!-- Waktu mulai & selesai -->
            <div class="wcal-row">
                <div>
                    <label class="wcal-lbl">Waktu Mulai</label>
                    <select class="wcal-select" x-model.number="creating.start">
                        <template x-for="m in startOptions" :key="m">
                            <option :value="m" x-text="fmtMin(m)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="wcal-lbl">Waktu Selesai</label>
                    <select class="wcal-select" x-model.number="creating.end">
                        <template x-for="m in endOptions" :key="m">
                            <option :value="m" x-text="fmtMin(m)"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Banner peringatan konflik -->
            <template x-if="creating.hardBlocked">
                <div class="wcal-banner red">
                    Slot ini sudah penuh. Pilih waktu lain.
                </div>
            </template>

            <!-- Tipe reservasi (3 jenis) -->
            <label class="wcal-lbl">Tipe Reservasi</label>
            <div class="wcal-typelist">
                <template x-for="o in loanOpts" :key="o.key">
                    <button class="wcal-typeopt" type="button"
                            :class="{ on: creating.loanType === o.key }"
                            :disabled="typeDisabled(o.key)"
                            @click="selectLoan(o.key)">
                        <span class="wcal-typesw" :style="{ background: o.fg }"></span>
                        <span class="wcal-typename" x-text="o.name"></span>
                        <span class="wcal-typebadge" :style="{ background: o.fg }"
                              x-text="o.badge"></span>
                    </button>
                </template>
            </div>

            <!-- Komputer only → single dropdown -->
            <template x-if="creating.loanType === 'computer'">
                <select class="wcal-select" x-model.number="selectedPc">
                    <option value="">Pilih unit…</option>
                    <template x-for="pc in pcList" :key="pc.id">
                        <option :value="pc.id" :disabled="!pc.available"
                                x-text="pc.label + (pc.available ? '' : ' · terpakai')">
                        </option>
                    </template>
                </select>
            </template>

            <!-- Room + Computer → multi checkbox grid -->
            <template x-if="creating.loanType === 'room_computer'">
                <div class="wcal-pcgrid">
                    <template x-for="pc in pcList" :key="pc.id">
                        <button type="button" class="wcal-pcbox"
                                :class="{ on: selectedPcs.includes(pc.id) }"
                                :disabled="!pc.available"
                                @click="togglePc(pc.id, pc.available)">
                            <span class="wcal-pcbox-label" x-text="pc.label"></span>
                            <span class="wcal-pcbox-status" x-text="pc.available
                                  ? 'tersedia' : 'terpakai'"></span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Room only → exclusive / shared toggle -->
            <template x-if="creating.loanType === 'room_only'">
                <div class="wcal-submode">
                    <button :class="{ on: creating.roomMode === 'shared' }"
                            @click="creating.roomMode = 'shared'">Berbagi</button>
                    <button :class="{ on: creating.roomMode === 'exclusive' }"
                            :disabled="roomModeDisabled('exclusive')"
                            @click="creating.roomMode = 'exclusive'">Eksklusif</button>
                </div>
            </template>

            <!-- Kategori, alasan, kebutuhan tambahan -->
            <label class="wcal-lbl">Kategori</label>
            <select class="wcal-select" x-model="category">
                <option value="penelitian">Penelitian</option>
                <option value="tugas_akhir">Tugas Akhir</option>
                <option value="project_akademik">Project Akademik</option>
                <option value="praktikum">Praktikum</option>
                <option value="lainnya" selected>Lainnya</option>
            </select>

            <label class="wcal-lbl">Alasan / Tujuan</label>
            <input type="text" class="wcal-input" x-model="reason"
                   maxlength="1000" placeholder="Tujuan peminjaman…">

            <label class="wcal-lbl">Kebutuhan Tambahan</label>
            <div style="display:flex; flex-direction:column; gap:7px; margin-bottom:14px;">
                <label><input type="checkbox" x-model="needsInternet"> Akses Internet</label>
                <label><input type="checkbox" x-model="needsInstallation"> Instalasi Software</label>
                <label><input type="checkbox" x-model="hasExternalDevices"> Perangkat Eksternal</label>
            </div>

            <div class="wcal-actions">
                <button class="wcal-btn" @click="creating = null">Batal</button>
                <button class="wcal-btn primary" :disabled="!canConfirm"
                        @click="submitBooking()">Konfirmasi Reservasi</button>
            </div>
        </div>
    </div>
</template>
```

#### Controller — Validasi dan Pemrosesan

File: `app/Http/Controllers/CalendarController.php`

```php
public function store(Request $request): RedirectResponse
{
    $validator = Validator::make($request->all(), [
        'booking_type' => ['required', Rule::in(['full_room', 'computers_only', 'room_only'])],
        'room_sharing' => ['nullable', 'required_if:booking_type,room_only',
                           Rule::in(['exclusive', 'shared'])],
        'date'         => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        'start_time'   => ['required', 'date_format:H:i'],
        'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
        'computers'    => ['array', 'required_if:booking_type,computers_only'],
        'computers.*'  => ['integer', Rule::in($computerIds)],
        'reason'       => ['required', 'string', 'min:3', 'max:1000'],
        'category'     => ['required', Rule::in([
            'penelitian', 'tugas_akhir', 'project_akademik', 'praktikum', 'lainnya'
        ])],
        'needs_internet'     => ['nullable'],
        'needs_installation' => ['nullable'],
        'external_devices'   => ['nullable'],
    ]);

    $validator->after(fn ($v) => $this->validateBusinessRules($request, $v));

    // ...

    $logbook = [
        'category'            => $data['category'],
        'checkpoint_progress' => $data['reason'],
        'needs_internet'      => (bool) ($data['needs_internet'] ?? false),
        'needs_installation'  => (bool) ($data['needs_installation'] ?? false),
        'external_devices'    => ($data['external_devices'] ?? '0') === '1' ? 'Ya' : null,
    ];

    $booking = $this->bookings->createBooking(auth()->id(), $schedule, $logbook);

    AuditLogService::record('booking.submitted', $booking, [], [
        'status'       => 'submitted',
        'booking_type' => $booking->booking_type,
    ]);

    return redirect()
        ->route('booking.show', $booking)
        ->with('success', 'Reservasi ' . $booking->booking_code . ' berhasil dikirim.');
}
```

#### Service — Deteksi Konflik dengan Lock

File: `app/Services/BookingService.php`

```php
public function checkConflict(
    string $date, string $startTime, string $endTime,
    string $bookingType, array $computerIds = [],
    ?string $roomSharing = null, ?int $excludeBookingId = null,
    bool $approvedOnly = false,
): bool {
    $statuses = $approvedOnly ? ['approved'] : self::ACTIVE_STATUSES;
    $buffer   = (int) LabSetting::get('buffer_minutes', 15);
    $buffEnd  = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i:s');

    $base = Booking::query()
        ->where('date', $date)
        ->whereIn('status', $statuses)
        ->where('start_time', '<', $buffEnd)
        ->where('end_time',   '>', $startTime)
        ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
        ->lockForUpdate();

    if ($bookingType === 'full_room') {
        return $base->exists();
    }

    if ($bookingType === 'computers_only') {
        // Konflik dengan full_room atau room_only exclusive
        if ((clone $base)->where('booking_type', 'full_room')->exists()) return true;
        if ((clone $base)->where('booking_type', 'room_only')
                ->where('room_sharing', 'exclusive')->exists()) return true;
        // Konflik hanya jika unit PC yang sama dipilih
        return (clone $base)->where('booking_type', 'computers_only')
            ->whereHas('computers', fn($q) =>
                $q->whereIn('computers.id', $computerIds))->exists();
    }

    if ($bookingType === 'room_only') {
        if ((clone $base)->where('booking_type', 'full_room')->exists()) return true;
        if ($roomSharing === 'exclusive') {
            return (clone $base)->whereIn('booking_type',
                ['room_only', 'computers_only'])->exists();
        }
        // Shared hanya konflik dengan exclusive room_only
        return (clone $base)->where('booking_type', 'room_only')
            ->where('room_sharing', 'exclusive')->exists();
    }

    return false;
}
```

---

### 4. Halaman Logbook (KF-06)

#### Deskripsi

Logbook kegiatan tersedia pada reservasi dengan status `approved` atau `completed`. Pengguna dapat mengisi dan mengedit catatan kegiatan, nama pembimbing, mata kuliah terkait, dan kebutuhan tambahan.

#### View — Formulir Logbook

File: `resources/views/booking/_logbook-form.blade.php`

```php
@php $lb = $booking->logbook; @endphp
<form method="POST" action="{{ route('booking.logbook.update', $booking) }}"
      class="bg-white border border-rule rounded-xl shadow-card p-6 space-y-5">
    @csrf
    @method('PUT')

    <div class="form-field">
        <label class="form-label form-required">Checkpoint / Progress Kegiatan</label>
        <textarea name="checkpoint_progress" class="form-textarea" rows="4"
                  placeholder="Jelaskan tahap kegiatan yang sudah diselesaikan…"
                  required>{{ old('checkpoint_progress',
                      $lb->checkpoint_progress ?? '') }}</textarea>
        <p class="form-hint">Deskripsikan apa yang berhasil diselesaikan (min. 10 karakter).</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="form-field">
            <label class="form-label">Nama Pembimbing</label>
            <input type="text" name="supervisor_name" class="form-input"
                   placeholder="cth. Dr. Budi Santoso"
                   value="{{ old('supervisor_name', $lb->supervisor_name ?? '') }}">
        </div>
        <div class="form-field">
            <label class="form-label">Mata Kuliah Terkait</label>
            <input type="text" name="related_course" class="form-input"
                   placeholder="cth. Kecerdasan Buatan"
                   value="{{ old('related_course', $lb->related_course ?? '') }}">
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2 border-t border-rule">
        <button type="submit" class="btn-mark">
            ✓ Simpan Logbook
        </button>
    </div>
</form>
```

#### Controller — Update Logbook dengan Audit Log

File: `app/Http/Controllers/BookingLogbookController.php`

Setiap perubahan logbook dicatat ke audit log dengan mekanisme *field-level diff* — hanya field yang benar-benar berubah yang direkam.

```php
public function update(Request $request, Booking $booking): RedirectResponse
{
    abort_if($booking->user_id !== auth()->id(), 403);
    abort_if(! in_array($booking->status, ['approved', 'completed']), 422);

    $validated = $request->validate([
        'checkpoint_progress' => ['required', 'string', 'min:10', 'max:2000'],
        'supervisor_name'     => ['nullable', 'string', 'max:255'],
        'related_course'      => ['nullable', 'string', 'max:255'],
    ]);

    $logbook = $booking->logbook;
    $old = $logbook->only(array_keys($validated));

    $logbook->update($validated);

    $new = $logbook->fresh()->only(array_keys($validated));
    $changed = array_filter($validated, fn ($v, $k) =>
        ($old[$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH);

    if (! empty($changed)) {
        AuditLogService::record('logbook.updated', $booking,
            Arr::only($old, array_keys($changed)),
            Arr::only($new, array_keys($changed)));
    }

    return back()->with('success', 'Logbook berhasil diperbarui.');
}
```

---

### 5. Daftar dan Detail Tinjauan Admin — Approve/Reject (KF-04, KF-07)

#### Deskripsi

Admin meninjau permintaan reservasi melalui halaman daftar yang mendukung filter status, tanggal, dan pencarian. Pada halaman detail, admin melihat:

- Informasi lengkap pemohon, jadwal, dan komputer yang dipilih
- Logbook kegiatan beserta kategori dan kebutuhan tambahan
- **Cek konflik live** terhadap reservasi yang sudah disetujui
- Panel persetujuan dengan *soft guard* modal untuk tanggal lampau
- Panel penolakan dengan form alasan wajib (min. 10 karakter)

#### Controller — Approve dengan Conflict Check dan Auto-Reject

File: `app/Http/Controllers/Admin/AdminRequestController.php`

```php
public function approve(Request $request, Booking $booking): RedirectResponse
{
    abort_if(! in_array($booking->status, ['submitted', 'under_review']), 422,
        'Permintaan ini sudah diproses.');

    // Soft guard: tanggal lampau memerlukan konfirmasi eksplisit
    if ($booking->date->lt(today()) && ! $request->boolean('confirm_past')) {
        return back()->with('warning_past', true);
    }

    $oldStatus = $booking->status;

    try {
        DB::transaction(function () use ($booking, $oldStatus) {
            // Re-check konflik dalam transaksi dengan lockForUpdate
            $conflict = $this->bookings->checkConflict(
                date:        $booking->date->format('Y-m-d'),
                startTime:   substr((string) $booking->start_time, 0, 5),
                endTime:     substr((string) $booking->end_time, 0, 5),
                bookingType: $booking->booking_type,
                computerIds: $booking->computers->pluck('id')->toArray(),
                roomSharing: $booking->room_sharing,
                excludeBookingId: $booking->id,
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'Slot ini sekarang bentrok. Persetujuan dibatalkan.'
                );
            }

            $booking->update([
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLogService::record('booking.approved', $booking,
                ['status' => $oldStatus], ['status' => 'approved']);

            // Auto-reject pending bookings yang konflik
            $this->bookings->autoRejectConflicting($booking);
        });
    } catch (BookingConflictException $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('admin.requests.index')
        ->with('success', 'Reservasi ' . $booking->booking_code . ' telah disetujui.');
}
```

#### View — Panel Persetujuan dengan Modal Tanggal Lampau

File: `resources/views/admin/requests/show.blade.php`

```php
<x-section label="Setujui">
    @php $isPastDate = $booking->date->lt(today()); @endphp

    @if ($isPastDate)
        <div class="p-3 rounded-lg bg-mark-500/10 border border-mark-500/30
                    flex items-start gap-2.5 mb-4">
            <svg class="w-4 h-4 text-mark-600"><!-- warning icon --></svg>
            <div>
                <span class="text-sm font-medium text-mark-700">
                    Tanggal reservasi sudah lewat
                </span>
                <p class="text-xs text-mark-600/70 mt-0.5">
                    {{ $booking->date->translatedFormat('d F Y') }}
                    — Anda masih dapat menyetujui dengan konfirmasi.
                </p>
            </div>
        </div>
    @endif

    <div x-data="{ showPastModal: {{ session('warning_past') ? 'true' : 'false' }} }">
        @if ($isPastDate)
            <button @click="showPastModal = true" class="w-full btn-mark justify-center">
                ✓ Setujui Permintaan
            </button>
        @else
            <form method="POST" action="{{ route('admin.requests.approve', $booking) }}"
                  @submit.prevent="if (confirm('Setujui reservasi?')) $el.submit()">
                @csrf
                <button type="submit" class="w-full btn-mark justify-center">
                    ✓ Setujui Permintaan
                </button>
            </form>
        @endif

        <!-- Modal konfirmasi tanggal lampau -->
        <div x-show="showPastModal" x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center
                    bg-ink-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
                <div class="px-6 py-5 bg-mark-500/10">
                    <h3>Konfirmasi Persetujuan</h3>
                    <p>{{ $booking->booking_code }}</p>
                </div>
                <div class="px-6 py-5">
                    <p>Tanggal reservasi
                       ({{ $booking->date->translatedFormat('d F Y') }}) sudah lewat.
                       Apakah Anda tetap ingin menyetujui?</p>
                </div>
                <div class="px-6 py-4 flex justify-end gap-3">
                    <button @click="showPastModal = false" class="btn-ghost btn-sm">
                        Batal
                    </button>
                    <form method="POST" action="{{ route('admin.requests.approve', $booking) }}">
                        @csrf
                        <input type="hidden" name="confirm_past" value="1">
                        <button type="submit" class="btn-mark btn-sm">Setujui</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-section>
```

#### View — Panel Penolakan

```php
<x-section label="Tolak">
    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open"
                class="w-full btn-danger btn-sm justify-center mb-3">
            Tolak Permintaan
        </button>
        <form method="POST" action="{{ route('admin.requests.reject', $booking) }}"
              x-show="open" x-transition class="space-y-3">
            @csrf
            <div class="form-field">
                <label class="form-label form-required">Alasan Penolakan</label>
                <textarea name="admin_notes" required minlength="10" maxlength="2000"
                          rows="4" class="form-textarea"
                          placeholder="Jelaskan alasan penolakan…">{{ old('admin_notes') }}</textarea>
            </div>
            <button type="submit" class="w-full btn-danger justify-center">
                Konfirmasi Penolakan
            </button>
        </form>
    </div>
</x-section>
```

---

### 6. Manajemen Status Komputer Individual (KF-08)

#### Deskripsi

Admin mengelola 9 unit komputer laboratorium. Setiap unit memiliki status (`online`, `maintenance`, `offline`) dengan aturan transisi yang ketat:

| Dari | Ke (diizinkan) |
|---|---|
| `online` | `online`, `maintenance`, `offline` |
| `maintenance` | `maintenance`, `online` |
| `offline` | `offline`, `online` |

#### Controller — Validasi Transisi Status

File: `app/Http/Controllers/Admin/AdminComputerController.php`

```php
class AdminComputerController extends Controller
{
    private const ALLOWED_TRANSITIONS = [
        'online'      => ['online', 'maintenance', 'offline'],
        'maintenance' => ['maintenance', 'online'],
        'offline'     => ['offline', 'online'],
    ];

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
        $newStatus = $request->status;

        $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];
        if (! in_array($newStatus, $allowed, true)) {
            return back()->with('error',
                'Transisi status tidak valid: ' . $oldStatus . ' → ' . $newStatus . '.');
        }

        $computer->update([
            'status'     => $newStatus,
            'specs_note' => $request->filled('specs_note')
                ? $request->specs_note : $computer->specs_note,
        ]);

        // Hanya audit jika ada field yang berubah
        $changedFields = [];
        if ($oldStatus !== $newStatus) {
            $changedFields['old']['status'] = $oldStatus;
            $changedFields['new']['status'] = $newStatus;
        }

        if (! empty($changedFields)) {
            AuditLogService::record('computer.status_changed', $computer,
                $changedFields['old'] ?? [], $changedFields['new'] ?? []);
        }

        return back()->with('success', $computer->label . ' diperbarui.');
    }
}
```

---

### 7. Log Audit (KF-09)

#### Deskripsi

Sistem mencatat seluruh aktivitas penting secara *append-only* ke tabel `audit_logs`. Halaman audit log admin menampilkan timeline yang dapat difilter berdasarkan jenis aksi, aktor, rentang tanggal, dan pencarian teks bebas. Setiap entri menampilkan:

- Aksi yang dilakukan dan deskripsi dalam Bahasa Indonesia
- Nama aktor (pengguna) atau "Sistem" untuk aksi otomatis
- Target (kode booking, label komputer, nama pengguna, dll.)
- Diff before/after untuk perubahan logbook
- Inline diff untuk perubahan status komputer

#### Controller — Presentasi dan Filtering

File: `app/Http/Controllers/Admin/AdminAuditLogController.php`

```php
class AdminAuditLogController extends Controller
{
    private const PRESENTATION = [
        'booking.submitted'       => ['Permintaan baru dikirim',         '#D9A300'],
        'booking.approved'        => ['Reservasi disetujui',             '#16A34A'],
        'booking.rejected'        => ['Reservasi ditolak',               '#DC2626'],
        'booking.auto_rejected'   => ['Ditolak otomatis (bentrok slot)', '#DC2626'],
        'booking.completed'       => ['Reservasi diselesaikan',          '#0891B2'],
        'booking.cancelled'       => ['Reservasi dibatalkan',            '#6B7280'],
        'logbook.updated'         => ['Logbook diperbarui',              '#C99400'],
        'computer.status_changed' => ['Status unit komputer diubah',     '#4A5568'],
        'user.created'            => ['Akun dosen dibuat',               '#7C3AED'],
        'user.updated'            => ['Akun dosen diperbarui',           '#7C3AED'],
        'team.created'            => ['Tim baru dibuat',                 '#7C3AED'],
        'team.updated'            => ['Tim diperbarui',                  '#7C3AED'],
        'settings.updated'        => ['Pengaturan lab diperbarui',       '#7C5CCF'],
    ];

    public function index(Request $request): View
    {
        $query = AuditLog::with(['user', 'auditable'])
            ->latest('created_at')->latest('id');

        // Filter: aksi (checkbox group)
        if ($request->has('af')) {
            $query->whereIn('action', (array) $request->input('actions', []));
        }
        // Filter: aktor
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }
        // Filter: rentang tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        // Filter: pencarian teks
        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($w) use ($term) {
                $w->where('action', 'like', "%{$term}%")
                  ->orWhereHasMorph('auditable', [Booking::class],
                      fn ($b) => $b->where('booking_code', 'like', "%{$term}%"));
            });
        }

        $stats = [
            'total'        => (clone $query)->count(),
            'today'        => (clone $query)->whereDate('created_at', today())->count(),
            'processed'    => AuditLog::whereIn('action', [
                'booking.approved', 'booking.rejected',
                'booking.auto_rejected', 'booking.completed',
            ])->count(),
            'active_users' => AuditLog::whereNotNull('user_id')
                ->distinct()->count('user_id'),
        ];

        $logs = $query->paginate(20)->withQueryString()
            ->through(fn ($log) => $this->present($log));

        return view('admin.audit-log.index', compact('logs', 'stats', ...));
    }

    private function present(AuditLog $log): array
    {
        [$desc, $color] = self::PRESENTATION[$log->action] ?? [$log->action, '#6B7280'];

        $target = match (true) {
            $log->auditable instanceof Booking  => $log->auditable->booking_code,
            $log->auditable instanceof Computer => $log->auditable->label,
            $log->auditable instanceof User     => $log->auditable->name,
            $log->auditable instanceof Team     => $log->auditable->name,
            $log->action === 'settings.updated' => 'lab_settings',
            default                             => '—',
        };

        return [
            'date_key'    => $log->created_at?->toDateString(),
            'day_label'   => $log->created_at?->translatedFormat('d F Y'),
            'day_rel'     => $this->relativeDay($log->created_at),
            'time'        => $log->created_at?->format('H:i'),
            'user'        => $log->user?->name ?? 'Sistem',
            'action'      => $log->action,
            'desc'        => $desc,
            'color'       => $color,
            'target'      => $target,
            'changes'     => $this->changes($log),
            'inline_diff' => $this->inlineDiff($log),
        ];
    }
}
```

#### Daftar Aksi yang Diaudit

| Aksi | Pemicu |
|---|---|
| `booking.submitted` | Pengguna mengajukan reservasi baru |
| `booking.approved` | Admin menyetujui reservasi |
| `booking.rejected` | Admin menolak reservasi |
| `booking.auto_rejected` | Sistem otomatis menolak reservasi yang bentrok |
| `booking.cancelled` | Pengguna membatalkan reservasi |
| `booking.completed` | Admin menandai reservasi selesai |
| `logbook.updated` | Pengguna mengedit logbook (field-level diff) |
| `computer.status_changed` | Admin mengubah status komputer |
| `user.created` / `user.updated` | Admin membuat/mengedit akun dosen |
| `team.created` / `team.updated` | Admin membuat/mengedit akun tim |
| `settings.updated` | Admin menyimpan pengaturan lab |

---

### 8. Dashboard Laporan (KF-11)

#### Deskripsi

Dashboard laporan menyajikan statistik peminjaman untuk rentang tanggal yang dapat dikonfigurasi. Admin dapat memilih preset periode (minggu, bulan, kuartal, tahun) atau menentukan rentang tanggal kustom. Data dihasilkan oleh `ReportService` yang mengagregasi data dari tabel `bookings`, `booking_logbooks`, `users`, dan `teams`.

> **Catatan:** Fitur ekspor PDF telah dihapus dari desain berdasarkan keputusan desain (D3 pada Phase 8). Visualisasi menggunakan CSS bar bukan Chart.js.

#### Controller — Resolusi Periode dan Pembuatan Laporan

File: `app/Http/Controllers/Admin/AdminReportController.php`

```php
class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $period = $request->input('period', 'month');

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to   = Carbon::parse($request->input('to'))->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to] = $this->resolvePeriod($period);
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from]; // Safety: perbaiki rentang terbalik
        }

        $data = $this->reports->generate($from, $to);

        return view('admin.reports.index', [
            'report' => $data, 'period' => $period,
            'from' => $from, 'to' => $to,
        ]);
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'week'    => [now()->startOfWeek(Carbon::MONDAY),
                         now()->endOfWeek(Carbon::SUNDAY)],
            'quarter' => [now()->subMonths(2)->startOfMonth(),
                         now()->endOfMonth()],
            'year'    => [now()->startOfYear(), now()->endOfYear()],
            default   => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
```

#### Service — Agregasi Data Laporan

File: `app/Services/ReportService.php`

```php
class ReportService
{
    private const COUNTED_STATUSES = ['submitted', 'under_review', 'approved', 'completed'];
    private const USED_STATUSES    = ['approved', 'completed'];

    public function generate(Carbon $from, Carbon $to): array
    {
        return [
            'summary'         => $this->summary($from, $to),
            'byType'          => $this->byType($from, $to),
            'byCategory'      => $this->byCategory($from, $to),
            'topUsers'        => $this->topUsers($from, $to),
            'utilization'     => $this->utilization($from, $to),
            'statusBreakdown' => $this->statusBreakdown($from, $to),
        ];
    }

    private function summary(Carbon $from, Carbon $to): array
    {
        $base = Booking::whereBetween('date', [$from, $to]);
        return [
            'total'     => (clone $base)
                ->whereIn('status', self::COUNTED_STATUSES)->count(),
            'approved'  => (clone $base)->where('status', 'approved')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'rejected'  => (clone $base)->where('status', 'rejected')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'total_hours' => round(
                (clone $base)->whereIn('status', self::USED_STATUSES)->get()
                    ->sum(fn ($b) => Carbon::parse($b->start_time)
                        ->diffInMinutes(Carbon::parse($b->end_time)) / 60), 1),
        ];
    }

    private function byCategory(Carbon $from, Carbon $to): array
    {
        return DB::table('bookings')
            ->join('booking_logbooks', 'bookings.id', '=', 'booking_logbooks.booking_id')
            ->whereBetween('bookings.date', [$from, $to])
            ->whereIn('bookings.status', self::COUNTED_STATUSES)
            ->select('booking_logbooks.category', DB::raw('COUNT(*) as count'))
            ->groupBy('booking_logbooks.category')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->category => $r->count])
            ->toArray();
    }

    private function topUsers(Carbon $from, Carbon $to): array
    {
        return DB::table('bookings')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->leftJoin('teams', 'users.id', '=', 'teams.user_id')
            ->whereBetween('bookings.date', [$from, $to])
            ->whereIn('bookings.status', self::COUNTED_STATUSES)
            ->select('users.id', 'users.name', 'users.role',
                     'teams.name as team_name',
                     DB::raw('COUNT(*) as booking_count'),
                     DB::raw('SUM(TIMESTAMPDIFF(MINUTE, bookings.start_time,
                              bookings.end_time)) as total_minutes'))
            ->groupBy('users.id', 'users.name', 'users.role', 'teams.name')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'name'  => $u->role === 'team' && $u->team_name
                    ? $u->team_name : $u->name,
                'role'  => match ($u->role) {
                    'team' => 'Tim', 'lecturer' => 'Dosen',
                    default => ucfirst($u->role),
                },
                'count' => $u->booking_count,
                'hours' => round(($u->total_minutes ?? 0) / 60, 1),
            ])
            ->toArray();
    }
}
```

#### Metrik Laporan yang Dihasilkan

| Metrik | Deskripsi |
|---|---|
| **Ringkasan** | Total, disetujui, selesai, ditolak, dibatalkan, total jam |
| **Per Tipe** | Distribusi berdasarkan `computers_only`, `full_room`, `room_only` |
| **Per Kategori** | Distribusi berdasarkan kategori logbook (penelitian, tugas akhir, dll.) |
| **Top Pengguna** | 5 pengguna teratas berdasarkan jumlah booking dan total jam |
| **Utilisasi** | Persentase pemanfaatan lab dibandingkan kapasitas operasional |
| **Status Breakdown** | Sebaran detail per status booking |
