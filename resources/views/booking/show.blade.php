<x-app-layout>

@php
    $typeLabelMap = [
        'full_room'      => 'Ruang + Komputer',
        'computers_only' => 'Komputer Saja',
        'room_only'      => 'Ruang Saja',
    ];
    $categoryMap = [
        'penelitian'       => 'Penelitian',
        'project_akademik' => 'Project Akademik',
        'praktikum'        => 'Praktikum',
        'tugas_akhir'      => 'Tugas Akhir / Skripsi',
        'lainnya'          => 'Lainnya',
    ];
    $dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    $typeLabel = $typeLabelMap[$booking->booking_type] ?? $booking->booking_type;
    if ($booking->booking_type === 'computers_only') {
        $typeLabel .= ' (' . $booking->computers->count() . ' unit)';
    }
    if ($booking->booking_type === 'room_only' && $booking->room_sharing) {
        $typeLabel .= ' (' . ($booking->room_sharing === 'exclusive' ? 'Eksklusif' : 'Berbagi') . ')';
    }

    $dateObj  = $booking->date;
    $dateFmt  = $dateObj->day . ' ' . $monthNames[$dateObj->month - 1] . ' ' . $dateObj->year;
    $dayName  = $dayNames[$dateObj->dayOfWeek];
    $start    = substr($booking->start_time, 0, 5);
    $end      = substr($booking->end_time, 0, 5);
    $durMin   = \Carbon\Carbon::parse($start)->diffInMinutes(\Carbon\Carbon::parse($end));
    $durHrs   = $durMin / 60;
    $durLabel = (intval($durHrs) == $durHrs)
        ? intval($durHrs) . ' jam'
        : floor($durHrs) . ' jam ' . ($durMin % 60) . ' menit';

    $submittedFmt = $booking->submitted_at?->translatedFormat('d M Y · H:i');
    $reviewedFmt  = $booking->reviewed_at?->translatedFormat('d M Y · H:i');

    $canEditLogbook = $booking->isEditable();
    $canCancel      = $booking->isCancellable();
@endphp

    <x-slot:header>
        <x-page-header
            eyebrow="Riwayat Reservasi"
            title="Detail Reservasi">
            <x-slot:actions>
                <a href="{{ route('booking.history') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">

        {{-- LEFT: Booking detail + Logbook --}}
        <div class="space-y-10">

            {{-- Booking info --}}
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
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                            <div class="col-span-2 text-sm font-medium text-ink-900">{{ $typeLabel }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                            <div class="col-span-2">
                                <div class="mono-data">{{ $dateFmt }}</div>
                                <div class="mono-code mt-0.5">{{ $dayName }}</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Waktu</div>
                            <div class="col-span-2">
                                <span class="mono-data">{{ $start }}</span>
                                <span class="mono-code mx-1">–</span>
                                <span class="mono-data">{{ $end }}</span>
                                <span class="mono-code ml-2">({{ $durLabel }})</span>
                            </div>
                        </div>
                        @if ($booking->computers->isNotEmpty())
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Unit</div>
                            <div class="col-span-2 flex flex-wrap gap-1.5">
                                @foreach ($booking->computers as $pc)
                                    <span class="font-mono text-xs px-2 py-0.5 rounded bg-ink-50 border border-rule-strong text-ink-900 font-semibold">
                                        {{ $pc->label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if ($booking->logbook)
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                            <div class="col-span-2 text-sm text-ink-900">{{ $categoryMap[$booking->logbook->category] ?? $booking->logbook->category }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Alasan</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed whitespace-pre-line">{{ $booking->logbook->checkpoint_progress }}</div>
                        </div>
                        @endif
                        @if ($submittedFmt)
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Diajukan</div>
                            <div class="col-span-2 mono-code">{{ $submittedFmt }}</div>
                        </div>
                        @endif
                        @if ($reviewedFmt && $booking->status === 'approved')
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Disetujui</div>
                            <div class="col-span-2 mono-code">{{ $reviewedFmt }}</div>
                        </div>
                        @endif
                        @if ($booking->status === 'rejected' && $booking->admin_notes)
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-status-rejected">Alasan Penolakan</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed">{{ $booking->admin_notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </x-section>

            {{-- Logbook section --}}
            <x-section label="Logbook Kegiatan">

                @if (! $canEditLogbook)
                    {{-- Not yet accessible --}}
                    <div class="flex gap-3 p-4 rounded-lg border border-rule bg-ink-50">
                        <svg class="w-5 h-5 text-ink-700/40 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-ink-900">Logbook belum tersedia</p>
                            <p class="text-sm text-ink-700/60 mt-0.5">
                                Logbook hanya dapat diisi dan diedit saat sesi
                                <span class="font-medium">sedang berlangsung</span> atau
                                <span class="font-medium">telah selesai</span>.
                                @if ($booking->status === 'rejected')
                                    Permintaan ini telah ditolak.
                                @elseif (in_array($booking->status, ['draft', 'submitted', 'under_review']))
                                    Tunggu persetujuan admin terlebih dahulu.
                                @elseif ($booking->status === 'cancelled')
                                    Reservasi ini telah dibatalkan.
                                @endif
                            </p>
                        </div>
                    </div>

                @elseif (! $booking->logbook || empty($booking->logbook->session_target))
                    {{-- Approved/completed but logbook (or its target field) not yet filled --}}
                    <div class="flex gap-3 p-4 rounded-lg border border-mark-300/50 bg-mark-50/60 mb-5">
                        <svg class="w-5 h-5 text-mark-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <p class="text-sm text-ink-700/80">
                            Logbook belum lengkap. Lengkapi target sesi dan catatan progress.
                        </p>
                    </div>
                    {{-- Logbook form --}}
                    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="btn-mark btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Isi Logbook
                        </button>
                        <div x-show="open" x-transition class="mt-5">
                            @include('booking._logbook-form', ['booking' => $booking])
                        </div>
                    </div>

                @else
                    {{-- Logbook filled — display + edit --}}
                    <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Checkpoint</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed whitespace-pre-line">{{ $booking->logbook->checkpoint_progress }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Target Sesi</div>
                            <div class="col-span-2 text-sm text-ink-700/80 whitespace-pre-line">{{ $booking->logbook->session_target ?? '—' }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pembimbing</div>
                            <div class="col-span-2 text-sm text-ink-900">{{ $booking->logbook->supervisor_name ?? '—' }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Mata Kuliah</div>
                            <div class="col-span-2 text-sm text-ink-900">{{ $booking->logbook->related_course ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-3" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="btn-secondary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Logbook
                        </button>
                        <span class="text-xs text-ink-700/40">Terakhir diperbarui: {{ $booking->logbook->updated_at?->translatedFormat('d M Y · H:i') }}</span>
                        <div x-show="open" x-transition class="w-full mt-3">
                            @include('booking._logbook-form', ['booking' => $booking])
                        </div>
                    </div>
                @endif

            </x-section>

        </div>

        {{-- RIGHT: Actions + Timeline --}}
        <div class="space-y-8">

            {{-- Cancel action --}}
            <x-section label="Tindakan">
                <div class="space-y-2">
                    @if ($canCancel)
                        <form method="POST" action="{{ route('booking.cancel', $booking) }}"
                              onsubmit="return confirm('Yakin batalkan reservasi {{ $booking->booking_code }}?');">
                            @csrf
                            <button type="submit" class="w-full btn-danger btn-sm justify-center">
                                Batalkan Reservasi
                            </button>
                        </form>
                        <p class="text-[11px] text-ink-700/40 text-center">Pembatalan tidak dapat dipulihkan</p>
                    @else
                        <button class="w-full btn-danger btn-sm justify-center opacity-40 cursor-not-allowed" disabled>
                            Batalkan Reservasi
                        </button>
                        <p class="text-[11px] text-ink-700/40 text-center">
                            @if ($booking->status === 'completed') Sesi telah selesai
                            @elseif ($booking->status === 'rejected') Permintaan ditolak
                            @elseif ($booking->status === 'cancelled') Sudah dibatalkan
                            @else Belum dapat dibatalkan
                            @endif
                        </p>
                    @endif
                </div>
            </x-section>

            {{-- Timeline --}}
            <x-section label="Riwayat Status">
                <ul class="space-y-5">
                    @if ($booking->status === 'cancelled')
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-status-cancelled mt-1 shrink-0"></span>
                            <span class="w-px flex-1 bg-rule mt-1"></span>
                        </div>
                        <div class="pb-5">
                            <p class="text-sm font-medium text-ink-900">Dibatalkan</p>
                            <p class="mono-code mt-0.5">{{ $booking->updated_at->translatedFormat('d M Y · H:i') }}</p>
                        </div>
                    </li>
                    @endif
                    @if ($booking->status === 'rejected' && $reviewedFmt)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-status-rejected mt-1 shrink-0"></span>
                            <span class="w-px flex-1 bg-rule mt-1"></span>
                        </div>
                        <div class="pb-5">
                            <p class="text-sm font-medium text-ink-900">Ditolak</p>
                            <p class="mono-code mt-0.5">{{ $reviewedFmt }}</p>
                            @if ($booking->reviewer)
                                <p class="text-xs text-ink-700/50 mt-1">oleh {{ $booking->reviewer->name }}</p>
                            @endif
                        </div>
                    </li>
                    @endif
                    @if (in_array($booking->status, ['approved', 'completed']) && $reviewedFmt)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-status-approved mt-1 shrink-0"></span>
                            <span class="w-px flex-1 bg-rule mt-1"></span>
                        </div>
                        <div class="pb-5">
                            <p class="text-sm font-medium text-ink-900">Disetujui</p>
                            <p class="mono-code mt-0.5">{{ $reviewedFmt }}</p>
                            @if ($booking->reviewer)
                                <p class="text-xs text-ink-700/50 mt-1">oleh {{ $booking->reviewer->name }}</p>
                            @endif
                        </div>
                    </li>
                    @endif
                    @if ($submittedFmt)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-ink-700/30 mt-1 shrink-0"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-ink-900">Diajukan</p>
                            <p class="mono-code mt-0.5">{{ $submittedFmt }}</p>
                        </div>
                    </li>
                    @endif
                </ul>
            </x-section>

        </div>

    </div>

</x-app-layout>
