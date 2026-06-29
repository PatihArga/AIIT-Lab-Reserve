<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Manajemen"
            title="Laporan & Analitik"
            meta="Ringkasan pemakaian Lab AIIT untuk periode terpilih.">
            <x-slot:actions>
                <div class="text-left sm:text-right text-xs text-ink-700/50 leading-relaxed">
                    <div>Periode aktif:
                        <span class="font-semibold text-ink-700/70 font-mono">{{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M Y') }}</span>
                    </div>
                    <div>Diperbarui
                        <span class="font-semibold text-ink-700/70 font-mono">{{ now()->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        // Indonesian number: comma decimal, dot thousands, trims trailing ",0".
        $num = function ($v) {
            $s = number_format((float) $v, 1, ',', '.');
            return rtrim(rtrim($s, '0'), ',');
        };

        // Initials from a display name (first two words).
        $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        $s = $report['summary'];

        // Weekly usage aggregates.
        $weekly      = $report['weeklyUsage'];
        $weeklyTotal = array_sum(array_column($weekly, 'value'));
        $peakWeek    = null;
        foreach ($weekly as $w) {
            if ($peakWeek === null || $w['value'] > $peakWeek['value']) { $peakWeek = $w; }
        }

        // Category donut geometry (r = 50 → circumference ≈ 314.16).
        $cats        = $report['categories'];
        $catTotal    = array_sum(array_column($cats, 'count'));
        $C           = 2 * M_PI * 50;
        $donutColors = ['#0A1A47', '#F5B800', '#0891B2', '#7C3AED', '#5585E8'];
        $segments    = [];
        $acc         = 0;
        foreach ($cats as $i => $c) {
            $frac = $catTotal > 0 ? $c['count'] / $catTotal : 0;
            $len  = $frac * $C;
            $segments[] = [
                'len'    => round($len, 2),
                'gap'    => round($C - $len, 2),
                'offset' => round(-$acc, 2),
                'color'  => $donutColors[$i % count($donutColors)],
            ];
            $acc += $len;
        }

        // Top-user footer totals (over the listed rows).
        $top           = $report['topUsers'];
        $topReservasi  = array_sum(array_column($top, 'count'));
        $topHours      = array_sum(array_column($top, 'hours'));

        // Per-computer: flag the busiest online unit(s) as "hot".
        $pcs   = $report['computerUsage'];
        $pcMax = 0;
        foreach ($pcs as $p) {
            if (! $p['maintenance']) { $pcMax = max($pcMax, $p['pct']); }
        }

        // Software installation reports.
        $installs = $report['installations'];
    @endphp

    {{-- ── Controls ───────────────────────────────────────────────
         Two independent GET forms: presets send ONLY `period`, the
         custom range sends ONLY `from`/`to`. This is the fix for the
         old single-form bug where pre-filled dates silently overrode
         the preset buttons. --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-7 print:hidden">
        <form method="GET" action="{{ route('admin.reports.index') }}"
              class="inline-flex items-center self-start bg-white border border-rule rounded-lg p-1 gap-1 shadow-card">
            @foreach (['week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'quarter' => '3 Bulan', 'year' => 'Tahun Ini'] as $val => $label)
                <button type="submit" name="period" value="{{ $val }}"
                        class="px-3.5 py-2 text-xs font-semibold rounded-md transition-colors whitespace-nowrap
                               {{ $period === $val ? 'bg-ink-900 text-white' : 'text-ink-700/70 hover:text-ink-900 hover:bg-ink-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>

        <div class="flex items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2 flex-wrap">
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                       class="form-input py-2 text-xs w-36" aria-label="Tanggal mulai">
                <span class="text-rule-strong font-semibold">–</span>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                       class="form-input py-2 text-xs w-36" aria-label="Tanggal selesai">
                <button type="submit" class="btn-mark btn-sm">Terapkan</button>
            </form>

            <a href="{{ route('admin.reports.export-pdf', request()->query()) }}"
               class="btn-secondary btn-sm" title="Unduh laporan sebagai PDF">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Unduh PDF
            </a>
        </div>
    </div>

    {{-- ── KPI band ──────────────────────────────────────────────
         `gap-px` over a rule-colored background renders hairline
         dividers that survive responsive wrapping. --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-rule border border-rule rounded-xl shadow-card overflow-hidden mb-6">

        {{-- Total reservasi --}}
        <div class="bg-white p-5 sm:p-6">
            <div class="flex items-center gap-2.5 mb-3.5">
                <span class="w-8 h-8 rounded-lg grid place-items-center bg-ink-50 text-ink-700 shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <span class="text-[0.7rem] font-semibold uppercase tracking-label text-ink-700/50">Total Reservasi</span>
            </div>
            <div class="font-mono text-3xl sm:text-4xl font-bold text-ink-900 leading-none">{{ $s['total_bookings'] }}</div>
            <div class="text-xs text-ink-700/60 mt-2.5">tercatat pada periode ini</div>
        </div>

        {{-- Tingkat pemakaian (the one amber-accented metric) --}}
        <div class="bg-white p-5 sm:p-6">
            <div class="flex items-center gap-2.5 mb-3.5">
                <span class="w-8 h-8 rounded-lg grid place-items-center bg-mark-50 text-mark-600 shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
                </span>
                <span class="text-[0.7rem] font-semibold uppercase tracking-label text-ink-700/50">Tingkat Pemakaian</span>
            </div>
            <div class="font-mono text-3xl sm:text-4xl font-bold text-ink-900 leading-none">{{ $s['utilization'] }}<span class="text-xl text-ink-700">%</span></div>
            <div class="text-xs text-ink-700/60 mt-2.5">{{ $num($s['used_hours']) }} dari {{ $num($s['available_hours']) }} jam tersedia</div>
        </div>

        {{-- Pengguna aktif --}}
        <div class="bg-white p-5 sm:p-6">
            <div class="flex items-center gap-2.5 mb-3.5">
                <span class="w-8 h-8 rounded-lg grid place-items-center bg-ink-50 text-ink-700 shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span class="text-[0.7rem] font-semibold uppercase tracking-label text-ink-700/50">Pengguna Aktif</span>
            </div>
            <div class="font-mono text-3xl sm:text-4xl font-bold text-ink-900 leading-none">{{ $s['active_users'] }}</div>
            <div class="text-xs text-ink-700/60 mt-2.5">dosen &amp; tim peneliti</div>
        </div>

        {{-- Rata-rata durasi --}}
        <div class="bg-white p-5 sm:p-6">
            <div class="flex items-center gap-2.5 mb-3.5">
                <span class="w-8 h-8 rounded-lg grid place-items-center bg-ink-50 text-ink-700 shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <span class="text-[0.7rem] font-semibold uppercase tracking-label text-ink-700/50">Rata-rata Durasi</span>
            </div>
            <div class="font-mono text-3xl sm:text-4xl font-bold text-ink-900 leading-none">{{ $num($s['avg_duration']) }}<span class="text-xl text-ink-700">j</span></div>
            <div class="text-xs text-ink-700/60 mt-2.5">per sesi peminjaman</div>
        </div>
    </div>

    {{-- ── Weekly usage + Category donut ─────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1.55fr_1fr] gap-6 mb-6">

        {{-- Weekly usage --}}
        <section class="bg-white border border-rule rounded-xl shadow-card p-6">
            <div class="flex items-baseline justify-between mb-5">
                <h2 class="text-sm font-bold text-ink-900">Pemakaian per Minggu</h2>
                <span class="text-[11px] text-ink-700/40">dalam jam</span>
            </div>

            @if (count($weekly) > 0 && $weeklyTotal > 0)
                <div class="space-y-4">
                    @foreach ($weekly as $w)
                        @php $pct = $w['max'] > 0 ? round(($w['value'] / $w['max']) * 100) : 0; @endphp
                        <div class="grid grid-cols-[34px_1fr_56px] items-center gap-3.5">
                            <span class="font-mono text-xs font-semibold text-ink-700/60">{{ $w['label'] }}</span>
                            <div class="h-3.5 rounded-full bg-ink-50 overflow-hidden" title="{{ $w['range'] }}">
                                <div class="h-full rounded-full transition-all {{ $w['value'] > 0 ? 'bg-mark-500' : 'bg-rule-strong' }}"
                                     style="width: {{ max($pct, $w['value'] > 0 ? 2 : 0) }}%"></div>
                            </div>
                            <span class="font-mono text-[13px] font-semibold text-right {{ $w['value'] > 0 ? 'text-ink-900' : 'text-ink-700/40' }}">{{ $num($w['value']) }} j</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-5 pt-4 border-t border-dashed border-rule text-xs text-ink-700/60">
                    <span>Puncak pada <b class="text-ink-900 font-semibold">{{ $peakWeek['label'] ?? '—' }}</b></span>
                    <span>Total <b class="text-ink-900 font-semibold">{{ $num($weeklyTotal) }} jam</b></span>
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-10 text-center">Tidak ada data pemakaian pada periode ini.</p>
            @endif
        </section>

        {{-- Category donut --}}
        <section class="bg-white border border-rule rounded-xl shadow-card p-6">
            <div class="flex items-baseline justify-between mb-5">
                <h2 class="text-sm font-bold text-ink-900">Kategori Peminjaman</h2>
                <span class="text-[11px] text-ink-700/40">{{ $catTotal }} reservasi</span>
            </div>

            @if ($catTotal > 0)
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="relative w-[132px] h-[132px] shrink-0">
                        <svg viewBox="0 0 120 120" width="132" height="132" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#EEF2FF" stroke-width="16"/>
                            @foreach ($segments as $seg)
                                <circle cx="60" cy="60" r="50" fill="none"
                                        stroke="{{ $seg['color'] }}" stroke-width="16"
                                        stroke-dasharray="{{ $seg['len'] }} {{ $seg['gap'] }}"
                                        stroke-dashoffset="{{ $seg['offset'] }}"/>
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="font-mono text-2xl font-bold text-ink-900 leading-none">{{ $catTotal }}</span>
                            <span class="text-[10px] uppercase tracking-label text-ink-700/40 mt-1">total</span>
                        </div>
                    </div>
                    <div class="flex-1 w-full flex flex-col gap-3">
                        @foreach ($cats as $i => $c)
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background: {{ $donutColors[$i % count($donutColors)] }}"></span>
                                <span class="text-[13px] text-ink-900 flex-1 capitalize">{{ $c['label'] }}</span>
                                <span class="font-mono text-[13px] font-semibold text-ink-900">{{ $c['count'] }}</span>
                                <span class="font-mono text-[11px] text-ink-700/40 w-9 text-right">{{ $c['pct'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-10 text-center">Tidak ada data kategori pada periode ini.</p>
            @endif
        </section>
    </div>

    {{-- ── Top users + Per-computer usage ───────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_1fr] gap-6">

        {{-- Top users --}}
        <section class="bg-white border border-rule rounded-xl shadow-card p-6 flex flex-col">
            <div class="flex items-baseline justify-between mb-4">
                <h2 class="text-sm font-bold text-ink-900">Pengguna Paling Aktif</h2>
                <span class="text-[11px] text-ink-700/40">berdasarkan total jam</span>
            </div>

            @if (count($top) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-8">#</th>
                            <th>Nama</th>
                            <th class="text-right">Reservasi</th>
                            <th class="text-right">Total Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($top as $i => $u)
                            @php $isTeam = $u['role'] === 'Tim'; @endphp
                            <tr>
                                <td class="font-mono font-bold align-middle {{ $i === 0 ? 'text-mark-500' : 'text-ink-700/40' }}">{{ $i + 1 }}</td>
                                <td class="align-middle">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-lg grid place-items-center text-xs font-bold shrink-0
                                                     {{ $isTeam ? 'bg-mark-50 text-mark-600' : 'bg-ink-900 text-white' }}">{{ $initials($u['name']) }}</span>
                                        <span class="min-w-0">
                                            <span class="block text-[13.5px] font-semibold text-ink-900 leading-tight truncate">{{ $u['name'] }}</span>
                                            <span class="block text-[11px] text-ink-700/40">{{ $u['role'] }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right font-mono font-semibold text-ink-700 align-middle">{{ $u['count'] }}</td>
                                <td class="text-right font-mono font-semibold text-ink-900 align-middle">{{ $num($u['hours']) }} j</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-auto pt-4 text-[11.5px] text-ink-700/60">
                    {{ count($top) }} pengguna ·
                    <b class="text-ink-900 font-semibold">{{ $topReservasi }} reservasi</b> ·
                    <b class="text-ink-900 font-semibold">{{ $num($topHours) }} jam</b> total
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-10 text-center">Tidak ada pengguna aktif pada periode ini.</p>
            @endif
        </section>

        {{-- Per-computer usage --}}
        <section class="bg-white border border-rule rounded-xl shadow-card p-6">
            <div class="flex items-baseline justify-between mb-4">
                <h2 class="text-sm font-bold text-ink-900">Pemakaian per Unit Komputer</h2>
                <span class="text-[11px] text-ink-700/40">{{ count($pcs) }} unit</span>
            </div>

            @if (count($pcs) > 0)
                <div class="flex flex-col gap-3">
                    @foreach ($pcs as $pc)
                        @php $hot = ! $pc['maintenance'] && $pc['pct'] > 0 && $pc['pct'] === $pcMax; @endphp
                        <div class="grid grid-cols-[52px_1fr_42px] items-center gap-3">
                            <span class="font-mono text-xs font-semibold {{ $pc['maintenance'] ? 'text-ink-700/30' : 'text-ink-700/60' }}">{{ $pc['label'] }}</span>
                            <div class="h-2.5 rounded-full bg-ink-50 overflow-hidden">
                                @if ($pc['maintenance'])
                                    <div class="h-full w-full bg-rule-strong/40"></div>
                                @else
                                    <div class="h-full rounded-full {{ $hot ? 'bg-mark-500' : 'bg-ink-700' }}" style="width: {{ $pc['pct'] }}%"></div>
                                @endif
                            </div>
                            <span class="font-mono text-[11.5px] font-semibold text-right {{ $pc['maintenance'] ? 'text-ink-700/30' : 'text-ink-700/50' }}">
                                {{ $pc['maintenance'] ? '—' : $pc['pct'] . '%' }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-5 mt-4 pt-4 border-t border-dashed border-rule text-[11.5px] text-ink-700/60">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-mark-500"></span> Paling sering</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-ink-700"></span> Reguler</span>
                </div>
            @else
                <p class="text-sm text-ink-700/50 py-10 text-center">Tidak ada data komputer.</p>
            @endif
        </section>
    </div>

    {{-- ── Software installations ───────────────────────────────
         What was installed during the period, and on which unit(s).
         Software is logged per session, so each row lists it against
         all units that session occupied. --}}
    <section class="bg-white border border-rule rounded-xl shadow-card p-6 mt-6">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="text-sm font-bold text-ink-900">Instalasi Perangkat Lunak</h2>
            <span class="text-[11px] text-ink-700/40">{{ count($installs) }} laporan</span>
        </div>

        @if (count($installs) > 0)
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-1/3">Unit / PC</th>
                            <th>Perangkat Lunak</th>
                            <th>Reservasi</th>
                            <th class="text-right whitespace-nowrap">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($installs as $ins)
                            <tr>
                                <td class="align-top">
                                    @if ($ins['all_units'])
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-mark-50 border border-mark-100 text-[11px] font-semibold text-mark-600">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                            Semua unit
                                        </span>
                                    @elseif (count($ins['units']) > 0)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($ins['units'] as $unit)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-ink-50 border border-rule font-mono text-[11px] font-semibold text-ink-700">{{ $unit }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-ink-700/30">—</span>
                                    @endif
                                </td>
                                <td class="align-top">
                                    <div class="text-[13px] text-ink-900 whitespace-pre-wrap break-words">{{ $ins['software'] }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="font-mono text-xs font-semibold text-ink-700">{{ $ins['booking_code'] }}</div>
                                    <div class="text-[11px] text-ink-700/40">{{ $ins['user'] }}</div>
                                </td>
                                <td class="align-top text-right font-mono text-xs text-ink-700/60 whitespace-nowrap">{{ $ins['date']->translatedFormat('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-ink-700/50 py-10 text-center">Tidak ada laporan instalasi perangkat lunak pada periode ini.</p>
        @endif
    </section>

</x-app-layout>
