<x-app-layout>

@php
    // Dummy: change this to test different states
    $booking = (object)[
        'code'       => 'LAB-0042',
        'type'       => 'Komputer Saja (3 unit)',
        'date'       => '12 Mei 2026',
        'day'        => 'Selasa',
        'start'      => '09:00',
        'end'        => '12:00',
        'duration'   => '3 jam',
        'computers'  => ['PC-01', 'PC-03', 'PC-05'],
        'category'   => 'Penelitian',
        'reason'     => 'Pengumpulan data eksperimen sensor suhu — sesi pengambilan data tahap 2.',
        'status'     => 'approved',  // draft | submitted | under_review | approved | rejected | cancelled | completed
        'submitted'  => '08 Mei 2026 · 14:32',
        'approved'   => '09 Mei 2026 · 10:15',
        // Logbook (null = not yet filled)
        'logbook' => null,
    ];

    $canEditLogbook = in_array($booking->status, ['approved', 'completed']);
    $canCancel      = $booking->status === 'approved';
@endphp

    <x-slot:header>
        <x-page-header
            eyebrow="Riwayat Reservasi"
            title="Detail Reservasi">
            <x-slot:actions>
                <a href="{{ route('booking.history') }}" class="btn-ghost btn-sm">← Kembali</a>
                <button class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Unduh PDF
                </button>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">

        {{-- LEFT: Booking detail + Logbook --}}
        <div class="space-y-10">

            {{-- Booking info --}}
            <x-section label="Informasi Reservasi">
                <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
                    <div class="px-6 py-4 bg-ink-900 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] uppercase tracking-label text-ink-100/50 font-semibold">Kode Reservasi</div>
                            <div class="font-mono text-white font-semibold text-base mt-0.5">{{ $booking->code }}</div>
                        </div>
                        <x-badge :status="$booking->status" />
                    </div>
                    <div class="divide-y divide-rule">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                            <div class="col-span-2 text-sm font-medium text-ink-900">{{ $booking->type }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                            <div class="col-span-2">
                                <div class="mono-data">{{ $booking->date }}</div>
                                <div class="mono-code mt-0.5">{{ $booking->day }}</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Waktu</div>
                            <div class="col-span-2">
                                <span class="mono-data">{{ $booking->start }}</span>
                                <span class="mono-code mx-1">–</span>
                                <span class="mono-data">{{ $booking->end }}</span>
                                <span class="mono-code ml-2">({{ $booking->duration }})</span>
                            </div>
                        </div>
                        @if (count($booking->computers))
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Unit</div>
                            <div class="col-span-2 flex flex-wrap gap-1.5">
                                @foreach ($booking->computers as $pc)
                                    <span class="font-mono text-xs px-2 py-0.5 rounded bg-ink-50 border border-rule-strong text-ink-900 font-semibold">
                                        {{ $pc }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                            <div class="col-span-2 text-sm text-ink-900">{{ $booking->category }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Alasan</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed">{{ $booking->reason }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Diajukan</div>
                            <div class="col-span-2 mono-code">{{ $booking->submitted }}</div>
                        </div>
                        @if ($booking->approved)
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Disetujui</div>
                            <div class="col-span-2 mono-code">{{ $booking->approved }}</div>
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

                @elseif ($booking->logbook === null)
                    {{-- Approved/completed but logbook not yet filled --}}
                    <div class="flex gap-3 p-4 rounded-lg border border-mark-300/50 bg-mark-50/60 mb-5">
                        <svg class="w-5 h-5 text-mark-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <p class="text-sm text-ink-700/80">
                            Logbook belum diisi. Isi logbook untuk mendokumentasikan kegiatan sesi ini.
                        </p>
                    </div>
                    {{-- Logbook form --}}
                    <div x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="btn-mark btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Isi Logbook
                        </button>
                        <div x-show="open" x-transition class="mt-5">
                            @include('booking._logbook-form')
                        </div>
                    </div>

                @else
                    {{-- Logbook filled — display + edit --}}
                    <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Checkpoint</div>
                            <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed">{{ $booking->logbook->checkpoint }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Target Sesi</div>
                            <div class="col-span-2 text-sm text-ink-700/80">{{ $booking->logbook->session_target ?? '—' }}</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pembimbing</div>
                            <div class="col-span-2 text-sm text-ink-900">{{ $booking->logbook->supervisor ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-3">
                        <button class="btn-secondary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Logbook
                        </button>
                        <span class="text-xs text-ink-700/40">Terakhir diperbarui: 09 Mei 2026 · 11:00</span>
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
                        <button class="w-full btn-danger btn-sm justify-center">
                            Batalkan Reservasi
                        </button>
                        <p class="text-[11px] text-ink-700/40 text-center">Berlaku hingga H-1 sebelum sesi</p>
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
                    @if ($booking->approved)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-status-approved mt-1 shrink-0"></span>
                            <span class="w-px flex-1 bg-rule mt-1"></span>
                        </div>
                        <div class="pb-5">
                            <p class="text-sm font-medium text-ink-900">Disetujui</p>
                            <p class="mono-code mt-0.5">{{ $booking->approved }}</p>
                            <p class="text-xs text-ink-700/50 mt-1">oleh Admin Lab</p>
                        </div>
                    </li>
                    @endif
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-ink-700/30 mt-1 shrink-0"></span>
                            <span class="w-px flex-1 bg-rule mt-1"></span>
                        </div>
                        <div class="pb-5">
                            <p class="text-sm font-medium text-ink-900">Diajukan</p>
                            <p class="mono-code mt-0.5">{{ $booking->submitted }}</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="w-2 h-2 rounded-full bg-ink-700/20 mt-1 shrink-0"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-ink-700/50">Draf dibuat</p>
                            <p class="mono-code mt-0.5">{{ $booking->submitted }}</p>
                        </div>
                    </li>
                </ul>
            </x-section>

        </div>

    </div>

</x-app-layout>
