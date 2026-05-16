<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Beranda Admin"
            title="Dashboard"
            meta="Diperbarui beberapa detik lalu · {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}">

            <x-slot:actions>
                <button class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Ekspor Laporan
                </button>
                <a href="{{ route('admin.requests.index') }}" class="btn-mark btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                    </svg>
                    Tinjau 3 Permintaan
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">

        {{-- Menunggu Tinjauan (primary, mark accent) --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5
                    border-l-[3px] border-l-mark-500 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Menunggu Tinjauan
                </div>
                <div class="w-8 h-8 rounded-md bg-mark-50 border border-mark-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">3</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">2 dalam 24 jam terakhir</div>
        </div>

        {{-- Disetujui Bulan Ini --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Disetujui Bulan Ini
                </div>
                <div class="w-8 h-8 rounded-md bg-ink-50 border border-rule flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-status-approved" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">47</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">
                <span class="text-status-approved font-semibold">↑ 12%</span> vs bulan lalu
            </div>
        </div>

        {{-- Unit Aktif --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Unit Aktif
                </div>
                <div class="w-8 h-8 rounded-md bg-ink-50 border border-rule flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">9</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">8 daring · 1 perawatan</div>
        </div>

        {{-- Pemakaian Minggu Ini --}}
        <div class="bg-white border border-rule rounded-xl shadow-card p-4 sm:p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-label font-bold text-ink-700/60 leading-tight">
                    Pemakaian Minggu Ini
                </div>
                <div class="w-8 h-8 rounded-md bg-ink-50 border border-rule flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <div class="text-[1.7rem] sm:text-[2rem] font-bold text-ink-900 leading-none tracking-tight">62%</div>
            <div class="text-[11px] sm:text-xs text-ink-700/50 mt-2">42 / 68 jam tersedia</div>
        </div>

    </div>

    {{-- Lab status mini-strip --}}
    <div class="bg-white border border-rule rounded-lg px-4 py-2.5 mb-8 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
        <div class="flex items-center gap-2 text-xs">
            <span class="w-2 h-2 rounded-sm" style="background:#2eb8a0"></span>
            <span class="font-mono font-semibold text-ink-900">8</span>
            <span class="text-ink-700/60">unit online</span>
        </div>
        <span class="hidden sm:inline text-ink-700/20">·</span>
        <div class="flex items-center gap-2 text-xs">
            <span class="w-2 h-2 rounded-sm bg-mark-500"></span>
            <span class="font-mono font-semibold text-ink-900">1</span>
            <span class="text-ink-700/60">perawatan</span>
        </div>
        <span class="hidden sm:inline text-ink-700/20">·</span>
        <div class="text-xs text-ink-700/60">
            Lab buka <span class="font-mono font-semibold text-ink-900">08:00–22:00</span> hari ini
        </div>
    </div>

    @php
        $pending = [
            ['code'=>'LAB-0042','applicant'=>'Tim Alpha','pic'=>'PIC: Dr. Budi Santoso','type'=>'Komputer + Ruang','date'=>'12 Mei 2026','time'=>'09:00 — 12:00','status'=>'pending'],
            ['code'=>'LAB-0043','applicant'=>'Dr. Siti Hartati','pic'=>'Dosen TI','type'=>'Komputer Saja (4 unit)','date'=>'13 Mei 2026','time'=>'14:00 — 16:00','status'=>'pending'],
            ['code'=>'LAB-0044','applicant'=>'Tim Beta','pic'=>'PIC: Prof. Andi W.','type'=>'Ruang Saja','date'=>'14 Mei 2026','time'=>'10:00 — 13:00','status'=>'pending'],
        ];

        $recent = [
            ['code'=>'LAB-0040','applicant'=>'Dr. Maria Lestari','type'=>'Komputer Saja (2)','date'=>'11 Mei 2026','time'=>'08:00 — 10:00','status'=>'approved'],
            ['code'=>'LAB-0039','applicant'=>'Tim Gamma','type'=>'Komputer + Ruang','date'=>'10 Mei 2026','time'=>'13:00 — 17:00','status'=>'completed'],
        ];
    @endphp

    {{-- Two-column: Pending requests + Activity feed --}}
    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-4 lg:gap-6">

        {{-- LEFT: Pending requests card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">

            {{-- Card header strip --}}
            <div class="px-5 py-4 border-b border-rule flex items-center justify-between gap-3">
                <div class="section-label flex items-baseline gap-2">
                    <span>Permintaan Aktif</span>
                    <span class="font-mono text-ink-700/40 normal-case tracking-normal">· 3</span>
                </div>
                <a href="{{ route('admin.requests.index') }}" class="btn-ghost btn-sm">
                    Lihat Semua
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @if (count($pending) === 0 && count($recent) === 0)
                <div class="p-5">
                    <x-empty-state
                        title="Belum ada permintaan"
                        desc="Permintaan baru dari pengguna akan muncul di sini." />
                </div>
            @else
                {{-- Mobile rows --}}
                <div class="lg:hidden divide-y divide-rule">
                    @foreach ($pending as $r)
                        <a href="{{ route('admin.requests.show', 1) }}"
                           class="block p-4 active:bg-ink-50/40 transition-colors border-l-[3px] border-l-mark-500">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="font-mono text-sm font-semibold text-ink-900">{{ $r['code'] }}</span>
                                <x-badge :status="$r['status']" />
                            </div>
                            <div class="text-sm font-medium text-ink-900">{{ $r['applicant'] }}</div>
                            <div class="text-xs text-ink-700/50 mb-2">{{ $r['pic'] }}</div>
                            <div class="text-sm text-ink-700/80 mb-1">{{ $r['type'] }}</div>
                            <div class="flex items-center gap-2 text-xs text-ink-700/60 font-mono">
                                <span>{{ $r['date'] }}</span>
                                <span class="text-ink-700/30">·</span>
                                <span>{{ $r['time'] }}</span>
                            </div>
                            <div class="flex items-center justify-end mt-3">
                                <span class="btn-ghost btn-sm pointer-events-none">Tinjau</span>
                            </div>
                        </a>
                    @endforeach

                    @if (count($recent) > 0)
                        <div class="px-4 py-2 bg-ink-50/50 text-[10px] uppercase tracking-label font-bold text-ink-700/50">
                            Baru Diproses
                        </div>
                    @endif

                    @foreach ($recent as $r)
                        <div class="p-4 opacity-80">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="font-mono text-sm font-semibold text-ink-700/60">{{ $r['code'] }}</span>
                                <x-badge :status="$r['status']" />
                            </div>
                            <div class="text-sm text-ink-700/70">{{ $r['applicant'] }}</div>
                            <div class="text-sm text-ink-700/60 mt-1">{{ $r['type'] }}</div>
                            <div class="flex items-center gap-2 text-xs text-ink-700/50 font-mono mt-1">
                                <span>{{ $r['date'] }}</span>
                                <span class="text-ink-700/30">·</span>
                                <span>{{ $r['time'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden lg:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="!pl-5">Kode</th>
                                <th>Pemohon</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-right !pr-5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pending as $r)
                                <tr class="row-mark">
                                    <td class="mono-data !pl-5">{{ $r['code'] }}</td>
                                    <td>
                                        <div class="font-medium">{{ $r['applicant'] }}</div>
                                        <div class="text-xs text-ink-700/50">{{ $r['pic'] }}</div>
                                    </td>
                                    <td class="text-ink-700/70">{{ $r['type'] }}</td>
                                    <td>
                                        <div class="mono-data text-ink-900">{{ $r['date'] }}</div>
                                        <div class="mono-code">{{ $r['time'] }}</div>
                                    </td>
                                    <td><x-badge :status="$r['status']" /></td>
                                    <td class="text-right !pr-5">
                                        <a href="{{ route('admin.requests.show', 1) }}" class="btn-ghost btn-sm">Tinjau</a>
                                    </td>
                                </tr>
                            @endforeach

                            @if (count($recent) > 0)
                                <tr class="bg-ink-50/50">
                                    <td colspan="6" class="!py-2 !pl-5 text-[10px] uppercase tracking-label font-bold text-ink-700/50">
                                        Baru Diproses
                                    </td>
                                </tr>
                            @endif

                            @foreach ($recent as $r)
                                <tr>
                                    <td class="mono-data text-ink-700/60 !pl-5">{{ $r['code'] }}</td>
                                    <td class="text-ink-700/70">{{ $r['applicant'] }}</td>
                                    <td class="text-ink-700/50">{{ $r['type'] }}</td>
                                    <td>
                                        <div class="mono-data text-ink-700/60">{{ $r['date'] }}</div>
                                        <div class="mono-code text-ink-700/40">{{ $r['time'] }}</div>
                                    </td>
                                    <td><x-badge :status="$r['status']" /></td>
                                    <td class="!pr-5"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- RIGHT: Activity feed card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden flex flex-col">

            {{-- Card header strip --}}
            <div class="px-5 py-4 border-b border-rule">
                <div class="section-label">Aktivitas Terkini</div>
            </div>

            {{-- Body --}}
            <ul class="p-5 space-y-5 flex-1">
                <li class="flex gap-3">
                    <div class="w-1.5 mt-1.5 shrink-0">
                        <span class="block w-1.5 h-1.5 rounded-full bg-mark-500"></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-ink-900">
                            <span class="font-semibold">Tim Alpha</span> mengajukan permintaan baru
                        </p>
                        <p class="text-xs text-ink-700/50 mono-code mt-0.5">LAB-0042 · 14 menit lalu</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <div class="w-1.5 mt-1.5 shrink-0">
                        <span class="block w-1.5 h-1.5 rounded-full bg-status-approved"></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-ink-900">
                            Anda menyetujui <span class="mono-data">LAB-0040</span>
                        </p>
                        <p class="text-xs text-ink-700/50 mono-code mt-0.5">2 jam lalu</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <div class="w-1.5 mt-1.5 shrink-0">
                        <span class="block w-1.5 h-1.5 rounded-full bg-ink-700/30"></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-ink-900">
                            <span class="font-semibold">PC-07</span> ditandai untuk pemeliharaan
                        </p>
                        <p class="text-xs text-ink-700/50 mono-code mt-0.5">Kemarin · 16:42</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <div class="w-1.5 mt-1.5 shrink-0">
                        <span class="block w-1.5 h-1.5 rounded-full bg-status-rejected"></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-ink-900">
                            Anda menolak <span class="mono-data">LAB-0038</span>
                        </p>
                        <p class="text-xs text-ink-700/50 mt-0.5">"Bentrok dengan slot praktikum"</p>
                        <p class="text-xs text-ink-700/50 mono-code mt-0.5">2 hari lalu</p>
                    </div>
                </li>
            </ul>

            {{-- Footer strip --}}
            <a href="{{ route('admin.audit-log.index') }}"
               class="px-5 py-3 border-t border-rule text-xs font-semibold uppercase tracking-label text-ink-700/70 hover:text-ink-900 hover:bg-ink-50/50 transition-colors flex items-center justify-between">
                <span>Buka Audit Log</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Computer status overview --}}
    <x-section label="Status Komputer" title="9 Unit · Lab 401" class="mt-12 sm:mt-16">
        <x-slot:actions>
            <a href="{{ route('admin.computers.index') }}" class="btn-ghost btn-sm">Kelola</a>
        </x-slot:actions>

        @php
            $dummyComputers = collect(range(1, 9))->map(fn($n) => (object) [
                'id'     => $n,
                'label'  => 'PC-' . str_pad($n, 2, '0', STR_PAD_LEFT),
                'status' => $n === 7 ? 'maintenance' : 'online',
            ]);
        @endphp

        <x-computer-grid :computers="$dummyComputers" />
    </x-section>

</x-app-layout>
