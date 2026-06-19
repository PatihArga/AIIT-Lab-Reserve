<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Permintaan Reservasi"
            title="Tinjauan Permintaan">
            <x-slot:actions>
                <a href="{{ route('admin.requests.index') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        $typeLabel = match ($booking->booking_type) {
            'full_room'      => 'Komputer + Ruang (Seluruh Lab)',
            'computers_only' => 'Komputer Saja',
            'room_only'      => $booking->room_sharing === 'shared' ? 'Ruang Saja (Berbagi)' : 'Ruang Saja (Eksklusif)',
            default          => $booking->booking_type,
        };

        $start = \Carbon\Carbon::parse($booking->start_time);
        $end   = \Carbon\Carbon::parse($booking->end_time);
        $durationHours = round($start->diffInMinutes($end) / 60, 2);

        $categoryLabel = match (optional($booking->logbook)->category) {
            'penelitian'       => 'Penelitian',
            'project_akademik' => 'Project Akademik',
            'praktikum'        => 'Praktikum',
            'tugas_akhir'      => 'Tugas Akhir',
            'lainnya'          => 'Lainnya',
            default            => '—',
        };

        $isPending = in_array($booking->status, ['submitted', 'under_review']);
        $isProcessed = in_array($booking->status, ['approved', 'rejected', 'completed']);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">

        {{-- LEFT: Full detail --}}
        <div class="space-y-10">

            {{-- Booking summary --}}
            <x-section label="Informasi Reservasi">
                <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
                    <div class="px-6 py-4 bg-ink-900 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] uppercase tracking-label text-ink-100/50 font-semibold">Kode Reservasi</div>
                            <div class="font-mono text-white font-semibold text-base mt-0.5">{{ $booking->booking_code }}</div>
                        </div>
                        <x-badge :status="$booking->status" />
                    </div>
                    <div class="divide-y divide-rule">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pemohon</div>
                            <div class="col-span-2">
                                <div class="font-medium text-ink-900">{{ $booking->user->name }}</div>
                                <div class="text-xs text-ink-700/50">
                                    {{ ucfirst($booking->user->role) }}
                                    @if ($booking->user->teamAccount?->picLecturer)
                                        · PIC: {{ $booking->user->teamAccount->picLecturer->name }}
                                    @endif
                                    @if ($booking->user->studyProgram)
                                        · {{ $booking->user->studyProgram->name }}
                                    @endif
                                </div>
                                <div class="text-xs text-ink-700/40 mt-0.5">{{ $booking->user->email }}</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                            <div class="col-span-2 text-sm font-medium text-ink-900">{{ $typeLabel }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                            <div class="col-span-2">
                                <div class="mono-data">{{ $booking->date->translatedFormat('d F Y') }}</div>
                                <div class="mono-code mt-0.5">{{ $booking->date->translatedFormat('l') }}</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Waktu</div>
                            <div class="col-span-2">
                                <span class="mono-data">{{ substr($booking->start_time, 0, 5) }}</span>
                                <span class="mono-code mx-1">–</span>
                                <span class="mono-data">{{ substr($booking->end_time, 0, 5) }}</span>
                                <span class="mono-code ml-2">({{ rtrim(rtrim(number_format($durationHours, 2), '0'), '.') }} jam)</span>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Diajukan</div>
                            <div class="col-span-2 mono-code">
                                {{ optional($booking->submitted_at)->translatedFormat('d M Y · H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-section>

            {{-- Computer grid (skip for room_only) --}}
            @if ($booking->booking_type !== 'room_only')
                <x-section label="Unit Komputer">
                    @if ($booking->booking_type === 'computers_only')
                        <x-computer-grid :computers="$booking->computers" />
                        <p class="form-hint mt-2">{{ $booking->computers->count() }} unit dipilih untuk sesi ini.</p>
                    @else
                        {{-- full_room: show all computers read-only --}}
                        @php $allComputers = \App\Models\Computer::orderBy('unit_number')->get(); @endphp
                        <x-computer-grid :computers="$allComputers" />
                        <p class="form-hint mt-2">Seluruh lab dipesan — semua unit aktif akan digunakan.</p>
                    @endif
                </x-section>
            @endif

            {{-- Logbook --}}
            <x-section label="Logbook Kegiatan">
                @if ($booking->logbook)
                    <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                            <div class="col-span-2 text-sm font-medium text-ink-900">{{ $categoryLabel }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Checkpoint</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed whitespace-pre-wrap">{{ $booking->logbook->checkpoint_progress }}</div>
                        </div>
                        @if ($booking->logbook->related_course)
                            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                                <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Mata Kuliah</div>
                                <div class="col-span-2 text-sm text-ink-900">{{ $booking->logbook->related_course }}</div>
                            </div>
                        @endif
                        @if ($booking->logbook->supervisor_name)
                            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                                <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pembimbing</div>
                                <div class="col-span-2 text-sm text-ink-900">{{ $booking->logbook->supervisor_name }}</div>
                            </div>
                        @endif
                        <div class="px-6 py-4 flex items-center gap-3 flex-wrap">
                            <span class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50 mr-1">Kebutuhan</span>
                            @php
                                $hasNeeds = $booking->logbook->needs_internet
                                         || $booking->logbook->needs_installation
                                         || $booking->logbook->external_devices;
                            @endphp
                            @if ($hasNeeds)
                                @if ($booking->logbook->needs_internet)
                                    <span class="badge-outline text-[10px]">Internet</span>
                                @endif
                                @if ($booking->logbook->needs_installation)
                                    <span class="badge-outline text-[10px]">Instalasi Software</span>
                                @endif
                                @if ($booking->logbook->external_devices)
                                    <span class="badge-outline text-[10px]">Perangkat Eksternal</span>
                                @endif
                            @else
                                <span class="text-xs text-ink-700/50">Tidak ada kebutuhan tambahan.</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white border border-rule rounded-xl shadow-card p-6 text-sm text-ink-700/50">
                        Logbook belum diisi.
                    </div>
                @endif
            </x-section>

            {{-- Already-reviewed info --}}
            @if ($isProcessed)
                <x-section label="Hasil Tinjauan">
                    <div class="bg-white border border-rule rounded-xl shadow-card p-5 space-y-3">
                        <div class="flex items-center gap-2">
                            <x-badge :status="$booking->status" />
                            <span class="text-xs text-ink-700/60 font-mono">
                                {{ optional($booking->reviewed_at)->translatedFormat('d M Y · H:i') }}
                            </span>
                        </div>
                        @if ($booking->reviewer)
                            <p class="text-sm text-ink-700/80">Ditinjau oleh <span class="font-semibold text-ink-900">{{ $booking->reviewer->name }}</span></p>
                        @endif
                        @if ($booking->admin_notes)
                            <div class="border-l-2 border-status-rejected/40 pl-3 py-1 text-sm text-ink-700/80 whitespace-pre-wrap">{{ $booking->admin_notes }}</div>
                        @endif
                    </div>
                </x-section>
            @endif

        </div>

        {{-- RIGHT: Approve / Reject panel --}}
        <div class="space-y-8">

            {{-- Conflict check --}}
            <x-section label="Cek Konflik">
                @if ($hasConflict)
                    <div class="p-3 rounded-lg bg-status-rejected/10 border border-status-rejected/30 flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-status-rejected shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99l-6.93-12a2 2 0 00-3.48 0l-6.93 12A2 2 0 005.07 19z"/>
                        </svg>
                        <span class="text-sm font-medium text-status-rejected">Ada konflik dengan reservasi lain</span>
                    </div>
                    <p class="text-xs text-ink-700/60 mt-2">Slot {{ $booking->date->translatedFormat('d M Y') }} · {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} sudah dipesan. Persetujuan akan dibatalkan otomatis jika konflik masih ada saat tombol ditekan.</p>
                @else
                    <div class="p-3 rounded-lg bg-status-approved/10 border border-status-approved/20 flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-status-approved shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-status-approved">Tidak ada konflik jadwal</span>
                    </div>
                    <p class="text-xs text-ink-700/50 mt-2">Slot {{ $booking->date->translatedFormat('d M Y') }} · {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} masih kosong.</p>
                @endif
            </x-section>

            @if ($isPending)
                {{-- Approve action --}}
                <x-section label="Setujui">
                    @php $isPastDate = $booking->date->lt(today()); @endphp

                    @if ($isPastDate)
                        {{-- Past-date warning banner --}}
                        <div class="p-3 rounded-lg bg-mark-500/10 border border-mark-500/30 flex items-start gap-2.5 mb-4">
                            <svg class="w-4 h-4 text-mark-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99l-6.93-12a2 2 0 00-3.48 0l-6.93 12A2 2 0 005.07 19z"/>
                            </svg>
                            <div>
                                <span class="text-sm font-medium text-mark-700">Tanggal reservasi sudah lewat</span>
                                <p class="text-xs text-mark-600/70 mt-0.5">{{ $booking->date->translatedFormat('d F Y') }} — Anda masih dapat menyetujui dengan konfirmasi.</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-ink-700/60 mb-4">
                            Menyetujui akan mengunci slot dan mencatat tindakan ke audit log.
                        </p>
                    @endif

                    <div x-data="{ showPastModal: {{ session('warning_past') ? 'true' : 'false' }} }">
                        {{-- Approve button: past dates open modal, future dates use simple confirm --}}
                        @if ($isPastDate)
                            <button type="button" @click="showPastModal = true" class="w-full btn-mark justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Setujui Permintaan
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.requests.approve', $booking) }}"
                                  x-data @submit.prevent="if (confirm('Setujui reservasi {{ $booking->booking_code }}?')) $el.submit()">
                                @csrf
                                <button type="submit" class="w-full btn-mark justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Setujui Permintaan
                                </button>
                            </form>
                        @endif

                        {{-- Past-date confirmation modal --}}
                        <div x-show="showPastModal" x-transition.opacity
                             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/50 backdrop-blur-sm"
                             @keydown.escape.window="showPastModal = false" x-cloak>
                            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden"
                                 @click.outside="showPastModal = false">
                                {{-- Modal header --}}
                                <div class="px-6 py-5 bg-mark-500/10 border-b border-mark-500/20">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-mark-500/20 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.99l-6.93-12a2 2 0 00-3.48 0l-6.93 12A2 2 0 005.07 19z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold text-ink-900">Konfirmasi Persetujuan</h3>
                                            <p class="text-xs text-ink-700/50 mt-0.5">{{ $booking->booking_code }}</p>
                                        </div>
                                    </div>
                                </div>
                                {{-- Modal body --}}
                                <div class="px-6 py-5">
                                    <p class="text-sm text-ink-700/80 leading-relaxed">
                                        Tanggal reservasi pengguna (<span class="font-semibold text-ink-900">{{ $booking->date->translatedFormat('d F Y') }}</span>) sudah lewat. Apakah Anda tetap ingin menyetujui?
                                    </p>
                                </div>
                                {{-- Modal actions --}}
                                <div class="px-6 py-4 bg-ink-50/50 border-t border-rule flex items-center justify-end gap-3">
                                    <button type="button" @click="showPastModal = false"
                                            class="btn-ghost btn-sm">
                                        Batal
                                    </button>
                                    <form method="POST" action="{{ route('admin.requests.approve', $booking) }}">
                                        @csrf
                                        <input type="hidden" name="confirm_past" value="1">
                                        <button type="submit" class="btn-mark btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Setujui
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-section>

                {{-- Reject action --}}
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
                                <textarea name="admin_notes" required minlength="10" maxlength="2000" rows="4"
                                          class="form-textarea @error('admin_notes') border-status-rejected @enderror"
                                          placeholder="Jelaskan alasan penolakan kepada pemohon…">{{ old('admin_notes') }}</textarea>
                                @error('admin_notes')
                                    <p class="text-xs text-status-rejected mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full btn-danger justify-center">
                                Konfirmasi Penolakan
                            </button>
                        </form>
                    </div>
                </x-section>
            @endif

            @if ($booking->status === 'approved')
                <x-section label="Tandai Selesai">
                    <p class="text-sm text-ink-700/60 mb-4">
                        Tandai reservasi sebagai selesai setelah sesi berakhir.
                    </p>
                    <form method="POST" action="{{ route('admin.requests.complete', $booking) }}"
                          x-data @submit.prevent="if (confirm('Tandai {{ $booking->booking_code }} sebagai selesai?')) $el.submit()">
                        @csrf
                        <button type="submit" class="w-full btn-ghost justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Tandai Selesai
                        </button>
                    </form>
                </x-section>
            @endif

        </div>

    </div>

</x-app-layout>
