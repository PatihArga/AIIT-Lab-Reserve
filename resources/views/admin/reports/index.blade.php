<x-app-layout>

@push('styles')
<style>
.bar-wrap { display: flex; align-items: flex-end; gap: 4px; height: 80px; }
.bar      { flex: 1; border-radius: 3px 3px 0 0; background: rgba(10,26,71,.15); transition: background .2s; position: relative; }
.bar:hover { background: #F5B800; }
.bar-label { position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); font-size: 9px; font-weight: 600; text-transform: uppercase; color: rgba(10,26,71,.4); white-space: nowrap; }
</style>
@endpush

    <x-slot:header>
        <x-page-header
            eyebrow="Manajemen"
            title="Laporan & Analitik"
            meta="Data pemakaian laboratorium">
            <x-slot:actions>
                <button class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Ekspor Excel
                </button>
                <button class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Ekspor PDF
                </button>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    {{-- Date range filter --}}
    <div class="flex items-center gap-3 mb-8 flex-wrap" x-data="{ period: 'month' }">
        @foreach (['week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'quarter' => '3 Bulan', 'year' => 'Tahun Ini'] as $val => $label)
            <button type="button"
                    @click="period = '{{ $val }}'"
                    :class="period === '{{ $val }}' ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300'"
                    class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all">
                {{ $label }}
            </button>
        @endforeach
        <div class="ml-auto flex items-center gap-2">
            <input type="date" class="form-input py-1.5 text-xs w-36" value="{{ date('Y-m-01') }}">
            <span class="text-ink-700/40 text-sm">–</span>
            <input type="date" class="form-input py-1.5 text-xs w-36" value="{{ date('Y-m-d') }}">
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 mb-16">
        <x-stat-hero value="47" label="Total Reservasi" meta="Bulan ini" />
        <x-stat-hero value="62%" label="Tingkat Pemakaian" meta="42 dari 68 jam tersedia" />
        <x-stat-hero value="9" label="Pengguna Aktif" meta="Dosen & Tim" />
        <x-stat-hero value="3.2j" label="Rata-rata Durasi" meta="per sesi" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

        {{-- Usage by week (bar chart) --}}
        <x-section label="Pemakaian per Minggu (Jam)">
            @php
                $weekData = [
                    ['label'=>'W1','value'=>14,'max'=>20],
                    ['label'=>'W2','value'=>20,'max'=>20],
                    ['label'=>'W3','value'=>9,'max'=>20],
                    ['label'=>'W4','value'=>17,'max'=>20],
                ];
            @endphp
            <div class="bar-wrap mb-8">
                @foreach ($weekData as $bar)
                    <div class="bar" style="height: {{ round(($bar['value']/$bar['max'])*100) }}%;" title="{{ $bar['value'] }}j">
                        <span class="bar-label">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 space-y-2">
                @foreach ($weekData as $bar)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-ink-700/50 w-5">{{ $bar['label'] }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-rule-strong overflow-hidden">
                            <div class="h-full bg-mark-500 rounded-full" style="width: {{ round(($bar['value']/$bar['max'])*100) }}%"></div>
                        </div>
                        <span class="mono-code text-ink-900 w-10 text-right">{{ $bar['value'] }}j</span>
                    </div>
                @endforeach
            </div>
        </x-section>

        {{-- Category breakdown --}}
        <x-section label="Breakdown Kategori">
            @php
                $categories = [
                    ['label'=>'Penelitian','count'=>18,'pct'=>38,'color'=>'bg-ink-700'],
                    ['label'=>'Praktikum','count'=>14,'pct'=>30,'color'=>'bg-mark-500'],
                    ['label'=>'Project Akademik','count'=>9,'pct'=>19,'color'=>'bg-[#2eb8a0]'],
                    ['label'=>'Tugas Akhir','count'=>4,'pct'=>9,'color'=>'bg-status-review'],
                    ['label'=>'Lainnya','count'=>2,'pct'=>4,'color'=>'bg-rule-strong'],
                ];
            @endphp
            <div class="space-y-3">
                @foreach ($categories as $cat)
                    <div class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full {{ $cat['color'] }} shrink-0"></span>
                        <span class="text-sm text-ink-700/80 flex-1">{{ $cat['label'] }}</span>
                        <div class="w-24 h-1.5 rounded-full bg-rule-strong overflow-hidden">
                            <div class="h-full rounded-full {{ $cat['color'] }}" style="width: {{ $cat['pct'] }}%"></div>
                        </div>
                        <span class="mono-code text-ink-900 w-8 text-right">{{ $cat['count'] }}</span>
                        <span class="mono-code text-ink-700/40 w-8 text-right">{{ $cat['pct'] }}%</span>
                    </div>
                @endforeach
            </div>
        </x-section>

        {{-- Most active users --}}
        <x-section label="Pengguna Paling Aktif">
            <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Reservasi</th>
                            <th>Total Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['rank'=>1,'name'=>'Tim Alpha','role'=>'Tim','count'=>12,'hours'=>36],
                            ['rank'=>2,'name'=>'Dr. Budi Santoso','role'=>'Dosen','count'=>8,'hours'=>22],
                            ['rank'=>3,'name'=>'Tim Gamma','role'=>'Tim','count'=>7,'hours'=>21],
                            ['rank'=>4,'name'=>'Dr. Siti Hartati','role'=>'Dosen','count'=>6,'hours'=>14],
                            ['rank'=>5,'name'=>'Tim Beta','role'=>'Tim','count'=>4,'hours'=>12],
                        ] as $u)
                            <tr>
                                <td class="mono-data text-ink-700/40">{{ $u['rank'] }}</td>
                                <td>
                                    <div class="font-medium text-ink-900">{{ $u['name'] }}</div>
                                    <div class="text-xs text-ink-700/40">{{ $u['role'] }}</div>
                                </td>
                                <td class="mono-data">{{ $u['count'] }}</td>
                                <td class="mono-data">{{ $u['hours'] }}j</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-section>

        {{-- Computer usage --}}
        <x-section label="Pemakaian per Unit Komputer">
            @php
                $pcUsage = [
                    ['label'=>'PC-01','pct'=>78],
                    ['label'=>'PC-02','pct'=>65],
                    ['label'=>'PC-03','pct'=>82],
                    ['label'=>'PC-04','pct'=>45],
                    ['label'=>'PC-05','pct'=>91],
                    ['label'=>'PC-06','pct'=>55],
                    ['label'=>'PC-07','pct'=>0,'maintenance'=>true],
                    ['label'=>'PC-08','pct'=>38],
                    ['label'=>'PC-09','pct'=>12],
                ];
            @endphp
            <div class="space-y-2.5">
                @foreach ($pcUsage as $pc)
                    <div class="flex items-center gap-3">
                        <span class="mono-code w-10 text-ink-700/60 shrink-0">{{ $pc['label'] }}</span>
                        <div class="flex-1 h-2 rounded-full bg-rule-strong overflow-hidden">
                            @if (isset($pc['maintenance']) && $pc['maintenance'])
                                <div class="h-full w-full bg-rule-strong rounded-full"></div>
                            @else
                                <div class="h-full rounded-full bg-mark-500" style="width: {{ $pc['pct'] }}%"></div>
                            @endif
                        </div>
                        <span class="mono-code text-ink-900 w-8 text-right text-xs">
                            @if (isset($pc['maintenance']) && $pc['maintenance'])
                                <span class="text-ink-700/30">—</span>
                            @else
                                {{ $pc['pct'] }}%
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </x-section>

    </div>

</x-app-layout>
