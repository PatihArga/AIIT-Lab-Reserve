<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Tinjau & Kirim">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Jadwal', 'Informasi', 'Tinjau']"
                    :current="3" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        $schedule = $draft['schedule'];
        $logbook  = $draft['logbook'];

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

        $dayNames      = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $monthNames    = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $dateObj       = \Carbon\Carbon::parse($schedule['date']);
        $dayName       = $dayNames[$dateObj->dayOfWeek];
        $dateFormatted = $dateObj->day . ' ' . $monthNames[$dateObj->month - 1] . ' ' . $dateObj->year;

        $start = substr($schedule['start_time'], 0, 5);
        $end   = substr($schedule['end_time'], 0, 5);
        $durationMin   = \Carbon\Carbon::parse($start)->diffInMinutes(\Carbon\Carbon::parse($end));
        $durationHours = $durationMin / 60;
        $durationLabel = (intval($durationHours) == $durationHours)
            ? intval($durationHours) . ' jam'
            : floor($durationHours) . ' jam ' . ($durationMin % 60) . ' menit';

        $typeLabel = $typeLabelMap[$schedule['type']] ?? $schedule['type'];
        if ($schedule['type'] === 'room_only' && !empty($schedule['room_sharing'])) {
            $typeLabel .= ' (' . ($schedule['room_sharing'] === 'exclusive' ? 'Eksklusif' : 'Berbagi') . ')';
        }
    @endphp

    <div class="max-w-2xl mx-auto">

        <p class="text-sm text-ink-700/60 mb-8">
            Periksa kembali semua detail sebelum mengirim permintaan. Setelah dikirim, data tidak dapat diubah tanpa persetujuan admin.
        </p>

        @if (session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Summary card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden mb-6">

            {{-- Header strip --}}
            <div class="px-6 py-4 bg-ink-900 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase tracking-label text-ink-100/50 font-semibold">Draf Permintaan</div>
                    <div class="font-mono text-white font-semibold text-sm mt-0.5">LAB-XXXX</div>
                </div>
                <x-badge status="draft" />
            </div>

            {{-- Detail rows --}}
            <div class="divide-y divide-rule">

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                    <div class="col-span-2 text-sm font-medium text-ink-900">{{ $typeLabel }}</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                    <div class="col-span-2">
                        <div class="mono-data text-ink-900">{{ $dateFormatted }}</div>
                        <div class="mono-code mt-0.5">{{ $dayName }}</div>
                    </div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Waktu</div>
                    <div class="col-span-2">
                        <span class="mono-data">{{ $start }}</span>
                        <span class="mono-code mx-1">–</span>
                        <span class="mono-data">{{ $end }}</span>
                        <span class="mono-code ml-2">({{ $durationLabel }})</span>
                    </div>
                </div>

                @if (!empty($computerLabels))
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Unit Dipilih</div>
                    <div class="col-span-2 flex flex-wrap gap-1.5">
                        @foreach ($computerLabels as $pc)
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-ink-50 border border-rule-strong text-ink-900 font-semibold">
                                {{ $pc }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                    <div class="col-span-2 text-sm text-ink-900">{{ $categoryMap[$logbook['category']] ?? $logbook['category'] }}</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Alasan</div>
                    <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed whitespace-pre-line">{{ $logbook['checkpoint_progress'] }}</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Mata Kuliah</div>
                    <div class="col-span-2 text-sm text-ink-900">{{ $logbook['related_course'] }}</div>
                </div>

                @if (!empty($logbook['supervisor_name']))
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pembimbing</div>
                    <div class="col-span-2 text-sm text-ink-900">{{ $logbook['supervisor_name'] }}</div>
                </div>
                @endif

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Internet</div>
                    <div class="col-span-2">
                        @if (!empty($logbook['needs_internet']))
                            <span class="badge-approved text-[10px]">Dibutuhkan</span>
                        @else
                            <span class="text-xs text-ink-700/50">Tidak diperlukan</span>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- Notice --}}
        <div class="flex gap-3 p-4 rounded-lg bg-mark-50 border border-mark-300/40 mb-8">
            <svg class="w-5 h-5 text-mark-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-ink-700/80">
                Permintaan akan masuk ke antrean tinjauan admin. Anda akan menerima notifikasi setelah disetujui atau ditolak. Proses biasanya memakan waktu 1×24 jam kerja.
            </p>
        </div>

        <form method="POST" action="{{ route('booking.store') }}">
            @csrf
            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('booking.logbook') }}" class="btn-ghost">
                    ← Kembali
                </a>
                <button type="submit" class="btn-mark btn-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Kirim Permintaan
                </button>
            </div>
        </form>

    </div>

</x-app-layout>
