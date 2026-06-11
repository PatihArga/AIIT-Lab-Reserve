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
            meta="Data pemakaian laboratorium" />
    </x-slot:header>

    {{-- Date range filter --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-3 mb-8 flex-wrap">
        @foreach (['week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'quarter' => '3 Bulan', 'year' => 'Tahun Ini'] as $val => $label)
            <button type="submit" name="period" value="{{ $val }}"
                    class="px-3.5 py-1.5 text-xs font-semibold uppercase tracking-label border rounded-md transition-all
                           {{ $period === $val ? 'bg-ink-900 text-white border-ink-900' : 'bg-white text-ink-700/70 border-rule hover:border-ink-300' }}">
                {{ $label }}
            </button>
        @endforeach
        <div class="ml-auto flex items-center gap-2">
            <input type="date" name="from" class="form-input py-1.5 text-xs w-36"
                   value="{{ $from->format('Y-m-d') }}">
            <span class="text-ink-700/40 text-sm">–</span>
            <input type="date" name="to" class="form-input py-1.5 text-xs w-36"
                   value="{{ $to->format('Y-m-d') }}">
            <button type="submit" class="btn-ghost btn-sm">Terapkan</button>
        </div>
    </form>

    @php
        $s = $report['summary'];
    @endphp

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 mb-16">
        <x-stat-hero value="{{ $s['total_bookings'] }}" label="Total Reservasi"
                     meta="{{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M Y') }}" />
        <x-stat-hero value="{{ $s['utilization'] }}%" label="Tingkat Pemakaian"
                     meta="{{ $s['used_hours'] }} dari {{ $s['available_hours'] }} jam tersedia" />
        <x-stat-hero value="{{ $s['active_users'] }}" label="Pengguna Aktif"
                     meta="Dosen & Tim" />
        <x-stat-hero value="{{ $s['avg_duration'] }}j" label="Rata-rata Durasi"
                     meta="per sesi" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

        {{-- Usage by week (bar chart) --}}
        <x-section label="Pemakaian per Minggu (Jam)">
            @if (count($report['weeklyUsage']) > 0)
                <div class="bar-wrap mb-8">
                    @foreach ($report['weeklyUsage'] as $bar)
                        <div class="bar"
                             style="height: {{ $bar['max'] > 0 ? round(($bar['value']/$bar['max'])*100) : 0 }}%;"
                             title="{{ $bar['value'] }}j ({{ $bar['range'] }})">
                            <span class="bar-label">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 space-y-2">
                    @foreach ($report['weeklyUsage'] as $bar)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-ink-700/50 w-5">{{ $bar['label'] }}</span>
                            <div class="flex-1 h-1.5 rounded-full bg-rule-strong overflow-hidden">
                                <div class="h-full bg-mark-500 rounded-full"
                                     style="width: {{ $bar['max'] > 0 ? round(($bar['value']/$bar['max'])*100) : 0 }}%"></div>
                            </div>
                            <span class="mono-code text-ink-900 w-10 text-right">{{ $bar['value'] }}j</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-6 text-center">Tidak ada data pemakaian pada periode ini.</p>
            @endif
        </x-section>

        {{-- Category breakdown --}}
        <x-section label="Breakdown Kategori">
            @if (count($report['categories']) > 0)
                <div class="space-y-3">
                    @foreach ($report['categories'] as $cat)
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
            @else
                <p class="text-sm text-ink-700/50 py-6 text-center">Tidak ada data kategori pada periode ini.</p>
            @endif
        </x-section>

        {{-- Most active users --}}
        <x-section label="Pengguna Paling Aktif">
            @if (count($report['topUsers']) > 0)
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
                            @foreach ($report['topUsers'] as $i => $u)
                                <tr>
                                    <td class="mono-data text-ink-700/40">{{ $i + 1 }}</td>
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
            @else
                <p class="text-sm text-ink-700/50 py-6 text-center">Tidak ada pengguna aktif pada periode ini.</p>
            @endif
        </x-section>

        {{-- Computer usage --}}
        <x-section label="Pemakaian per Unit Komputer">
            @if (count($report['computerUsage']) > 0)
                <div class="space-y-2.5">
                    @foreach ($report['computerUsage'] as $pc)
                        <div class="flex items-center gap-3">
                            <span class="mono-code w-10 text-ink-700/60 shrink-0">{{ $pc['label'] }}</span>
                            <div class="flex-1 h-2 rounded-full bg-rule-strong overflow-hidden">
                                @if ($pc['maintenance'])
                                    <div class="h-full w-full bg-rule-strong rounded-full"></div>
                                @else
                                    <div class="h-full rounded-full bg-mark-500" style="width: {{ $pc['pct'] }}%"></div>
                                @endif
                            </div>
                            <span class="mono-code text-ink-900 w-8 text-right text-xs">
                                @if ($pc['maintenance'])
                                    <span class="text-ink-700/30">—</span>
                                @else
                                    {{ $pc['pct'] }}%
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-6 text-center">Tidak ada data komputer.</p>
            @endif
        </x-section>

    </div>

</x-app-layout>
