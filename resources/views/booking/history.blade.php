<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi"
            title="Riwayat Reservasi"
            meta="Semua permintaan yang pernah Anda buat">
            <x-slot:actions>
                <a href="{{ route('booking.create') }}" class="btn-mark btn-sm">
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
    <div class="flex items-center gap-3 mb-6 flex-wrap" x-data="{ status: 'all' }">
        @foreach (['all' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $val => $label)
            <button type="button"
                    @click="status = '{{ $val }}'"
                    :class="status === '{{ $val }}' ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300'"
                    class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all">
                {{ $label }}
            </button>
        @endforeach

        <div class="ml-auto flex items-center gap-2">
            <input type="search" placeholder="Cari kode…"
                   class="form-input py-1.5 text-xs w-44">
            <input type="date" class="form-input py-1.5 text-xs w-36">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
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
    <div class="flex items-center justify-between mt-4 text-xs text-ink-700/50">
        <span>Menampilkan 5 dari 5 entri</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">← Sebelumnya</button>
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">Berikutnya →</button>
        </div>
    </div>

</x-app-layout>
