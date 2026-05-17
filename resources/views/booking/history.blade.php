<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi"
            title="Riwayat Reservasi"
            meta="Semua permintaan yang pernah Anda buat">
            <x-slot:actions>
                <a href="{{ route('booking.schedule') }}" class="btn-mark btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Reservasi
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

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
        $monthShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $activeStatus = request('status', 'all');
    @endphp

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('booking.history') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
        {{-- Status chips --}}
        <div class="flex flex-wrap gap-2">
            @foreach (['all' => 'Semua', 'submitted' => 'Diajukan', 'under_review' => 'Tinjauan', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $val => $label)
                <a href="{{ route('booking.history', array_merge(request()->except('status', 'page'), $val === 'all' ? [] : ['status' => $val])) }}"
                   class="{{ $activeStatus === $val ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300' }} px-3 sm:px-3.5 py-1.5 text-[11px] sm:text-xs font-semibold uppercase tracking-label border rounded-md transition-all whitespace-nowrap inline-block">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Search + date --}}
        <div class="flex gap-2 sm:ml-auto">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode…"
                   class="form-input py-1.5 text-xs flex-1 sm:w-44 sm:flex-none">
            <input type="date" name="date" value="{{ request('date') }}"
                   class="form-input py-1.5 text-xs flex-1 sm:w-36 sm:flex-none">
            <button type="submit" class="btn-ghost btn-sm">Filter</button>
        </div>
    </form>

    @if ($bookings->isEmpty())
        <x-empty-state
            title="Belum ada reservasi"
            desc="Reservasi yang Anda buat akan muncul di sini." />
    @else

    {{-- Cards (mobile) --}}
    <div class="sm:hidden space-y-3">
        @foreach ($bookings as $b)
            @php
                $typeLabel = $typeLabelMap[$b->booking_type] ?? $b->booking_type;
                if ($b->booking_type === 'computers_only') {
                    $typeLabel .= ' (' . $b->computers->count() . ' unit)';
                }
                $dateObj = $b->date;
                $dateStr = $dateObj->day . ' ' . $monthShort[$dateObj->month - 1] . ' ' . $dateObj->year;
                $time    = substr($b->start_time, 0, 5) . ' – ' . substr($b->end_time, 0, 5);
            @endphp
            <a href="{{ route('booking.show', $b) }}"
               class="block bg-white border border-rule rounded-xl shadow-card p-4
                      hover:shadow-md active:scale-[0.99] transition-all
                      {{ in_array($b->status, ['submitted','under_review']) ? 'border-l-[3px] border-l-mark-500' : '' }}">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="font-mono text-sm font-semibold text-ink-900">{{ $b->booking_code }}</span>
                    <x-badge :status="$b->status" />
                </div>
                <div class="text-sm font-medium text-ink-900 mb-1">{{ $typeLabel }}</div>
                <div class="flex items-center gap-2 text-xs text-ink-700/60 font-mono">
                    <span>{{ $dateStr }}</span>
                    <span class="text-ink-700/30">·</span>
                    <span>{{ $time }}</span>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-rule">
                    <span class="text-xs text-ink-700/70">{{ $categoryMap[$b->logbook->category ?? null] ?? '—' }}</span>
                    <span class="text-xs font-semibold text-ink-700 inline-flex items-center gap-1">
                        Lihat
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Table (desktop) --}}
    <div class="hidden sm:block bg-white border border-rule rounded-xl shadow-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Jenis</th>
                    <th>Tanggal & Waktu</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $b)
                    @php
                        $typeLabel = $typeLabelMap[$b->booking_type] ?? $b->booking_type;
                        if ($b->booking_type === 'computers_only') {
                            $typeLabel .= ' (' . $b->computers->count() . ' unit)';
                        }
                        $dateObj = $b->date;
                        $dateStr = $dateObj->day . ' ' . $monthShort[$dateObj->month - 1] . ' ' . $dateObj->year;
                        $time    = substr($b->start_time, 0, 5) . ' – ' . substr($b->end_time, 0, 5);
                    @endphp
                    <tr class="{{ in_array($b->status, ['submitted','under_review']) ? 'row-mark' : '' }}">
                        <td class="mono-data">{{ $b->booking_code }}</td>
                        <td class="text-ink-700/80">{{ $typeLabel }}</td>
                        <td>
                            <div class="mono-data text-ink-900 text-sm">{{ $dateStr }}</div>
                            <div class="mono-code">{{ $time }}</div>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $categoryMap[$b->logbook->category ?? null] ?? '—' }}</td>
                        <td><x-badge :status="$b->status" /></td>
                        <td class="text-right">
                            <a href="{{ route('booking.show', $b) }}" class="btn-ghost btn-sm">Lihat</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $bookings->links() }}
    </div>

    @endif

</x-app-layout>
