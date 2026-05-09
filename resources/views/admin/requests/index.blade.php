<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Operasional"
            title="Permintaan Reservasi"
            meta="Semua permintaan masuk · {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}">
            <x-slot:actions>
                <button class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Ekspor
                </button>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        $requests = [
            ['code'=>'LAB-0044','user'=>'Tim Beta','meta'=>'PIC: Prof. Andi W.','type'=>'Ruang Saja','date'=>'14 Mei 2026','time'=>'10:00 – 13:00','category'=>'Penelitian','status'=>'pending'],
            ['code'=>'LAB-0043','user'=>'Dr. Siti Hartati','meta'=>'Dosen TI','type'=>'Komputer Saja (4)','date'=>'13 Mei 2026','time'=>'14:00 – 16:00','category'=>'Praktikum','status'=>'pending'],
            ['code'=>'LAB-0042','user'=>'Tim Alpha','meta'=>'PIC: Dr. Budi Santoso','type'=>'Komputer + Ruang','date'=>'12 Mei 2026','time'=>'09:00 – 12:00','category'=>'Penelitian','status'=>'pending'],
            ['code'=>'LAB-0041','user'=>'Dr. Maria Lestari','meta'=>'Dosen SI','type'=>'Komputer Saja (2)','date'=>'11 Mei 2026','time'=>'08:00 – 10:00','category'=>'Tugas Akhir','status'=>'under_review'],
            ['code'=>'LAB-0040','user'=>'Tim Gamma','meta'=>'PIC: Dr. Rina K.','type'=>'Komputer + Ruang','date'=>'10 Mei 2026','time'=>'13:00 – 17:00','category'=>'Project Akademik','status'=>'approved'],
            ['code'=>'LAB-0039','user'=>'Dr. Hendra P.','meta'=>'Dosen TI','type'=>'Ruang Saja','date'=>'09 Mei 2026','time'=>'10:00 – 12:00','category'=>'Penelitian','status'=>'rejected'],
            ['code'=>'LAB-0038','user'=>'Tim Delta','meta'=>'PIC: Dr. Lina S.','type'=>'Komputer + Ruang','date'=>'07 Mei 2026','time'=>'14:00 – 17:00','category'=>'Praktikum','status'=>'completed'],
        ];
    @endphp

    {{-- Status tabs --}}
    <div class="flex items-center gap-1 mb-6 flex-wrap" x-data="{ tab: 'all' }">
        @foreach (['all' => 'Semua', 'pending' => 'Menunggu', 'under_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'] as $val => $label)
            <button type="button"
                    @click="tab = '{{ $val }}'"
                    :class="tab === '{{ $val }}'
                        ? 'bg-ink-900 text-white border-ink-900'
                        : 'bg-white text-ink-700/70 border-rule hover:border-ink-300'"
                    class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all">
                {{ $label }}
                @if ($val === 'pending')
                    <span class="ml-1 font-mono bg-mark-500 text-ink-900 text-[10px] px-1.5 py-0.5 rounded font-semibold">3</span>
                @endif
            </button>
        @endforeach

        <div class="ml-auto flex items-center gap-2">
            <input type="search" placeholder="Cari kode / nama…"
                   class="form-input py-1.5 text-xs w-48">
            <select class="form-select py-1.5 text-xs w-36">
                <option>Semua kategori</option>
                <option>Penelitian</option>
                <option>Praktikum</option>
                <option>Tugas Akhir</option>
                <option>Project Akademik</option>
            </select>
            <input type="date" class="form-input py-1.5 text-xs w-36">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pemohon</th>
                    <th>Jenis</th>
                    <th>Jadwal</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $req)
                    <tr class="{{ $req['status'] === 'pending' ? 'row-mark' : '' }}">
                        <td class="mono-data">{{ $req['code'] }}</td>
                        <td>
                            <div class="font-medium text-ink-900">{{ $req['user'] }}</div>
                            <div class="text-xs text-ink-700/50">{{ $req['meta'] }}</div>
                        </td>
                        <td class="text-ink-700/70">{{ $req['type'] }}</td>
                        <td>
                            <div class="mono-data text-ink-900 text-sm">{{ $req['date'] }}</div>
                            <div class="mono-code">{{ $req['time'] }}</div>
                        </td>
                        <td class="text-ink-700/70 text-sm">{{ $req['category'] }}</td>
                        <td><x-badge :status="$req['status']" /></td>
                        <td class="text-right">
                            <a href="{{ route('admin.requests.show', 1) }}" class="btn-ghost btn-sm">Tinjau</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4 text-xs text-ink-700/50">
        <span>Menampilkan 7 dari 7 entri</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">← Sebelumnya</button>
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">Berikutnya →</button>
        </div>
    </div>

</x-app-layout>
