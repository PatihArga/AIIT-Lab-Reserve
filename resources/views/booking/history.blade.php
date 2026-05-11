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
        $bookings = [
            ['code'=>'LAB-0042','type'=>'Komputer Saja (3 unit)','date'=>'12 Mei 2026','time'=>'09:00 – 12:00','category'=>'Penelitian','status'=>'pending'],
            ['code'=>'LAB-0038','type'=>'Ruang + Komputer','date'=>'05 Mei 2026','time'=>'14:00 – 17:00','category'=>'Praktikum','status'=>'approved'],
            ['code'=>'LAB-0031','type'=>'Komputer Saja (2 unit)','date'=>'28 Apr 2026','time'=>'10:00 – 12:00','category'=>'Tugas Akhir / Skripsi','status'=>'completed'],
            ['code'=>'LAB-0027','type'=>'Ruang Saja','date'=>'20 Apr 2026','time'=>'13:00 – 15:00','category'=>'Project Akademik','status'=>'rejected'],
            ['code'=>'LAB-0019','type'=>'Ruang + Komputer','date'=>'10 Apr 2026','time'=>'08:00 – 11:00','category'=>'Penelitian','status'=>'cancelled'],
        ];
    @endphp

    {{-- Filter bar --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap" x-data="{ status: 'all' }">
        {{-- Status chips --}}
        <div class="flex flex-wrap gap-2">
            @foreach (['all' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $val => $label)
                <button type="button"
                        @click="status = '{{ $val }}'"
                        :class="status === '{{ $val }}' ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300'"
                        class="px-3 sm:px-3.5 py-1.5 text-[11px] sm:text-xs font-semibold uppercase tracking-label border rounded-md transition-all whitespace-nowrap">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Search + date --}}
        <div class="flex gap-2 sm:ml-auto">
            <input type="search" placeholder="Cari kode…"
                   class="form-input py-1.5 text-xs flex-1 sm:w-44 sm:flex-none">
            <input type="date" class="form-input py-1.5 text-xs flex-1 sm:w-36 sm:flex-none">
        </div>
    </div>

    {{-- Cards (mobile) --}}
    <div class="sm:hidden space-y-3">
        @foreach ($bookings as $b)
            <a href="{{ route('booking.show', 1) }}"
               class="block bg-white border border-rule rounded-xl shadow-card p-4
                      hover:shadow-md active:scale-[0.99] transition-all
                      {{ $b['status'] === 'pending' ? 'border-l-[3px] border-l-mark-500' : '' }}">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="font-mono text-sm font-semibold text-ink-900">{{ $b['code'] }}</span>
                    <x-badge :status="$b['status']" />
                </div>
                <div class="text-sm font-medium text-ink-900 mb-1">{{ $b['type'] }}</div>
                <div class="flex items-center gap-2 text-xs text-ink-700/60 font-mono">
                    <span>{{ $b['date'] }}</span>
                    <span class="text-ink-700/30">·</span>
                    <span>{{ $b['time'] }}</span>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-rule">
                    <span class="text-xs text-ink-700/70">{{ $b['category'] }}</span>
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
                    <tr class="{{ $b['status'] === 'pending' ? 'row-mark' : '' }}">
                        <td class="mono-data">{{ $b['code'] }}</td>
                        <td class="text-ink-700/80">{{ $b['type'] }}</td>
                        <td>
                            <div class="mono-data text-ink-900 text-sm">{{ $b['date'] }}</div>
                            <div class="mono-code">{{ $b['time'] }}</div>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $b['category'] }}</td>
                        <td><x-badge :status="$b['status']" /></td>
                        <td class="text-right">
                            <a href="{{ route('booking.show', 1) }}" class="btn-ghost btn-sm">Lihat</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination placeholder --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-4 text-xs text-ink-700/50">
        <span>Menampilkan 5 dari 5 entri</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">← Sebelumnya</button>
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">Berikutnya →</button>
        </div>
    </div>

</x-app-layout>
