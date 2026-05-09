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
                <a href="#" class="btn-mark btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                    </svg>
                    Tinjau 3 Permintaan
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    {{-- Hero stats: ONE primary stat (pending count, in mark) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 mb-16">
        <x-stat-hero
            value="3"
            label="Menunggu Tinjauan"
            meta="2 dalam 24 jam terakhir"
            mark />

        <x-stat-hero
            value="47"
            label="Disetujui Bulan Ini"
            meta="↑ 12% vs bulan lalu" />

        <x-stat-hero
            value="9"
            label="Unit Aktif"
            meta="Semua daring" />

        <x-stat-hero
            value="62%"
            label="Pemakaian Minggu Ini"
            meta="42 dari 68 jam tersedia" />
    </div>

    {{-- Two-column: Pending requests + Activity feed --}}
    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-12">

        {{-- LEFT: Pending requests --}}
        <x-section label="Permintaan Aktif" count="3">
            <x-slot:actions>
                <a href="#" class="text-xs uppercase tracking-label font-semibold text-ink-700/60 hover:text-ink-900">
                    Lihat Semua →
                </a>
            </x-slot:actions>

            <div class="overflow-hidden">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pemohon</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="row-mark">
                            <td class="mono-data">LAB-0042</td>
                            <td>
                                <div class="font-medium">Tim Alpha</div>
                                <div class="text-xs text-ink-700/50">PIC: Dr. Budi Santoso</div>
                            </td>
                            <td class="text-ink-700/70">Komputer + Ruang</td>
                            <td>
                                <div class="mono-data text-ink-900">12 Mei 2026</div>
                                <div class="mono-code">09:00 — 12:00</div>
                            </td>
                            <td><x-badge status="pending" /></td>
                            <td class="text-right">
                                <a href="#" class="btn-ghost btn-sm">Tinjau</a>
                            </td>
                        </tr>
                        <tr class="row-mark">
                            <td class="mono-data">LAB-0043</td>
                            <td>
                                <div class="font-medium">Dr. Siti Hartati</div>
                                <div class="text-xs text-ink-700/50">Dosen TI</div>
                            </td>
                            <td class="text-ink-700/70">Komputer Saja (4 unit)</td>
                            <td>
                                <div class="mono-data text-ink-900">13 Mei 2026</div>
                                <div class="mono-code">14:00 — 16:00</div>
                            </td>
                            <td><x-badge status="pending" /></td>
                            <td class="text-right">
                                <a href="#" class="btn-ghost btn-sm">Tinjau</a>
                            </td>
                        </tr>
                        <tr class="row-mark">
                            <td class="mono-data">LAB-0044</td>
                            <td>
                                <div class="font-medium">Tim Beta</div>
                                <div class="text-xs text-ink-700/50">PIC: Prof. Andi W.</div>
                            </td>
                            <td class="text-ink-700/70">Ruang Saja</td>
                            <td>
                                <div class="mono-data text-ink-900">14 Mei 2026</div>
                                <div class="mono-code">10:00 — 13:00</div>
                            </td>
                            <td><x-badge status="pending" /></td>
                            <td class="text-right">
                                <a href="#" class="btn-ghost btn-sm">Tinjau</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="mono-data text-ink-700/60">LAB-0040</td>
                            <td class="text-ink-700/70">Dr. Maria Lestari</td>
                            <td class="text-ink-700/50">Komputer Saja (2)</td>
                            <td>
                                <div class="mono-data text-ink-700/60">11 Mei 2026</div>
                                <div class="mono-code text-ink-700/40">08:00 — 10:00</div>
                            </td>
                            <td><x-badge status="approved" /></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="mono-data text-ink-700/60">LAB-0039</td>
                            <td class="text-ink-700/70">Tim Gamma</td>
                            <td class="text-ink-700/50">Komputer + Ruang</td>
                            <td>
                                <div class="mono-data text-ink-700/60">10 Mei 2026</div>
                                <div class="mono-code text-ink-700/40">13:00 — 17:00</div>
                            </td>
                            <td><x-badge status="completed" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-section>

        {{-- RIGHT: Activity feed --}}
        <x-section label="Aktivitas Terkini">
            <ul class="space-y-5">
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

            <a href="#" class="block mt-6 text-xs uppercase tracking-label font-semibold text-ink-700/60 hover:text-ink-900">
                Buka Audit Log →
            </a>
        </x-section>
    </div>

    {{-- Computer status overview --}}
    <x-section label="Status Komputer" title="9 Unit · Lab 401" class="mt-16">
        <x-slot:actions>
            <a href="#" class="btn-ghost btn-sm">Kelola</a>
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
