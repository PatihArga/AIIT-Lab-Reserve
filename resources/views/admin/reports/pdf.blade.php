{{--
    Server-side PDF template for Laporan & Analitik (dompdf).
    Standalone document — NO x-app-layout, NO Tailwind. Uses table layout and
    inline/internal CSS only (dompdf supports a subset of CSS 2.1).
--}}
@php
    // Indonesian number: comma decimal, dot thousands, trims trailing ",0".
    $num = function ($v) {
        $s = number_format((float) $v, 1, ',', '.');
        return rtrim(rtrim($s, '0'), ',');
    };

    $s = $report['summary'];

    $weekly      = $report['weeklyUsage'];
    $weeklyTotal = array_sum(array_column($weekly, 'value'));
    $peakWeek    = null;
    foreach ($weekly as $w) {
        if ($peakWeek === null || $w['value'] > $peakWeek['value']) { $peakWeek = $w; }
    }

    $cats     = $report['categories'];
    $catTotal = array_sum(array_column($cats, 'count'));

    $top          = $report['topUsers'];
    $topReservasi = array_sum(array_column($top, 'count'));
    $topHours     = array_sum(array_column($top, 'hours'));

    $pcs   = $report['computerUsage'];
    $pcMax = 0;
    foreach ($pcs as $p) {
        if (! $p['maintenance']) { $pcMax = max($pcMax, $p['pct']); }
    }

    $installs = $report['installations'];

    $periodLabels = [
        'week'    => 'Minggu Ini',
        'month'   => 'Bulan Ini',
        'quarter' => '3 Bulan Terakhir',
        'year'    => 'Tahun Ini',
        'custom'  => 'Rentang Kustom',
    ];
    $periodLabel = $periodLabels[$period] ?? ucfirst($period);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Lab AIIT UKRIDA</title>
    <style>
        @page { margin: 26px 32px 44px 32px; }

        * { font-family: 'DejaVu Sans', sans-serif; }

        body { margin: 0; color: #1a2236; font-size: 10px; line-height: 1.4; }

        /* ── Header band ── */
        .doc-header { background-color: #0A1A47; color: #ffffff; padding: 16px 18px; border-radius: 6px; }
        .doc-header .eyebrow { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: #aab4d4; margin: 0 0 4px 0; }
        .doc-header h1 { font-size: 17px; margin: 0; font-weight: bold; }
        .doc-header .meta { font-size: 9px; color: #cfd6ea; margin-top: 6px; }
        .doc-header .meta b { color: #F5B800; }

        /* ── Section titles ── */
        h2.section { font-size: 11px; color: #0A1A47; margin: 20px 0 7px 0; padding-bottom: 4px; border-bottom: 2px solid #0A1A47; }
        .section-note { font-size: 8px; color: #8089a3; font-weight: normal; }

        /* ── Tables ── */
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th { background-color: #f1f3f9; color: #515b78; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 6px 8px; border-bottom: 1px solid #d7dceb; }
        table.grid td { padding: 6px 8px; border-bottom: 1px solid #eceef5; font-size: 9.5px; }
        table.grid td.num, table.grid th.num { text-align: right; }
        table.grid tr.total td { font-weight: bold; background-color: #fafbfd; border-top: 1px solid #d7dceb; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; }

        /* ── KPI cards (as a 4-col table) ── */
        table.kpi { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-top: 10px; }
        table.kpi td { width: 25%; background-color: #f7f8fc; border: 1px solid #e4e8f3; border-radius: 5px; padding: 10px 11px; vertical-align: top; }
        table.kpi td.accent { background-color: #fff9e6; border-color: #f3e2a8; }
        .kpi-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #818aa6; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #0A1A47; margin-top: 5px; }
        .kpi-value .u { font-size: 11px; color: #5b6b8c; }
        .kpi-sub { font-size: 7.8px; color: #8a93ad; margin-top: 4px; }

        /* ── Bars ── */
        .bartrack { width: 150px; height: 8px; background-color: #edeff6; border-radius: 4px; }
        .barfill { height: 8px; border-radius: 4px; background-color: #0A1A47; }
        .barfill.amber { background-color: #F5B800; }
        .barfill.maint { background-color: #cfd4e2; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge.all { background-color: #fff3cc; color: #9a7400; }
        .badge.unit { background-color: #eef1f8; color: #44507a; font-family: 'DejaVu Sans Mono', monospace; }

        .empty { font-size: 9px; color: #97a0b8; padding: 12px 0; text-align: center; font-style: italic; }

        /* ── Footer ── */
        .foot { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 7.5px; color: #9aa2b8; }
        .foot .r { text-align: right; }
    </style>
</head>
<body>

    {{-- Fixed footer on every page --}}
    <table class="foot"><tr>
        <td>Lab AIIT UKRIDA — Laporan Pemakaian Laboratorium</td>
        <td class="r">Dicetak {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td>
    </tr></table>

    {{-- Header --}}
    <div class="doc-header">
        <p class="eyebrow">Laporan &amp; Analitik</p>
        <h1>Pemakaian Lab AIIT UKRIDA</h1>
        <div class="meta">
            Periode: <b>{{ $periodLabel }}</b>
            &nbsp;·&nbsp; {{ $from->translatedFormat('d M Y') }} – {{ $to->translatedFormat('d M Y') }}
        </div>
    </div>

    {{-- KPI band --}}
    <table class="kpi">
        <tr>
            <td>
                <div class="kpi-label">Total Reservasi</div>
                <div class="kpi-value">{{ $s['total_bookings'] }}</div>
                <div class="kpi-sub">tercatat pada periode ini</div>
            </td>
            <td class="accent">
                <div class="kpi-label">Tingkat Pemakaian</div>
                <div class="kpi-value">{{ $s['utilization'] }}<span class="u">%</span></div>
                <div class="kpi-sub">{{ $num($s['used_hours']) }} dari {{ $num($s['available_hours']) }} jam</div>
            </td>
            <td>
                <div class="kpi-label">Pengguna Aktif</div>
                <div class="kpi-value">{{ $s['active_users'] }}</div>
                <div class="kpi-sub">dosen &amp; tim peneliti</div>
            </td>
            <td>
                <div class="kpi-label">Rata-rata Durasi</div>
                <div class="kpi-value">{{ $num($s['avg_duration']) }}<span class="u">j</span></div>
                <div class="kpi-sub">per sesi peminjaman</div>
            </td>
        </tr>
    </table>

    {{-- Weekly usage --}}
    <h2 class="section">Pemakaian per Minggu <span class="section-note">(dalam jam)</span></h2>
    @if (count($weekly) > 0 && $weeklyTotal > 0)
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 50px;">Minggu</th>
                    <th style="width: 110px;">Rentang</th>
                    <th>Distribusi</th>
                    <th class="num" style="width: 70px;">Jam</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($weekly as $w)
                    @php $pct = $w['max'] > 0 ? round(($w['value'] / $w['max']) * 100) : 0; @endphp
                    <tr>
                        <td class="mono"><b>{{ $w['label'] }}</b></td>
                        <td class="mono">{{ $w['range'] }}</td>
                        <td>
                            <div class="bartrack">
                                <div class="barfill amber" style="width: {{ max($pct, $w['value'] > 0 ? 3 : 0) }}%;"></div>
                            </div>
                        </td>
                        <td class="num mono">{{ $num($w['value']) }} j</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="2">Puncak pada {{ $peakWeek['label'] ?? '—' }}</td>
                    <td></td>
                    <td class="num mono">{{ $num($weeklyTotal) }} j</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty">Tidak ada data pemakaian pada periode ini.</p>
    @endif

    {{-- Category breakdown --}}
    <h2 class="section">Kategori Peminjaman <span class="section-note">({{ $catTotal }} reservasi)</span></h2>
    @if ($catTotal > 0)
        <table class="grid">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="num" style="width: 90px;">Reservasi</th>
                    <th class="num" style="width: 90px;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cats as $c)
                    <tr>
                        <td style="text-transform: capitalize;">{{ $c['label'] }}</td>
                        <td class="num mono">{{ $c['count'] }}</td>
                        <td class="num mono">{{ $c['pct'] }}%</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total</td>
                    <td class="num mono">{{ $catTotal }}</td>
                    <td class="num mono">100%</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty">Tidak ada data kategori pada periode ini.</p>
    @endif

    {{-- Top users --}}
    <h2 class="section">Pengguna Paling Aktif <span class="section-note">(berdasarkan total jam)</span></h2>
    @if (count($top) > 0)
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 26px;">#</th>
                    <th>Nama</th>
                    <th style="width: 70px;">Peran</th>
                    <th class="num" style="width: 80px;">Reservasi</th>
                    <th class="num" style="width: 80px;">Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($top as $i => $u)
                    <tr>
                        <td class="mono"><b>{{ $i + 1 }}</b></td>
                        <td>{{ $u['name'] }}</td>
                        <td>{{ $u['role'] }}</td>
                        <td class="num mono">{{ $u['count'] }}</td>
                        <td class="num mono">{{ $num($u['hours']) }} j</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3">{{ count($top) }} pengguna</td>
                    <td class="num mono">{{ $topReservasi }}</td>
                    <td class="num mono">{{ $num($topHours) }} j</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty">Tidak ada pengguna aktif pada periode ini.</p>
    @endif

    {{-- Per-computer usage --}}
    <h2 class="section">Pemakaian per Unit Komputer <span class="section-note">({{ count($pcs) }} unit)</span></h2>
    @if (count($pcs) > 0)
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 70px;">Unit</th>
                    <th>Distribusi</th>
                    <th class="num" style="width: 70px;">Jam</th>
                    <th class="num" style="width: 80px;">Pemakaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pcs as $pc)
                    @php $hot = ! $pc['maintenance'] && $pc['pct'] > 0 && $pc['pct'] === $pcMax; @endphp
                    <tr>
                        <td class="mono">{{ $pc['label'] }}</td>
                        <td>
                            <div class="bartrack">
                                @if ($pc['maintenance'])
                                    <div class="barfill maint" style="width: 100%;"></div>
                                @else
                                    <div class="barfill {{ $hot ? 'amber' : '' }}" style="width: {{ max($pc['pct'], 1) }}%;"></div>
                                @endif
                            </div>
                        </td>
                        <td class="num mono">{{ $pc['maintenance'] ? '—' : $num($pc['hours']) . ' j' }}</td>
                        <td class="num mono">{{ $pc['maintenance'] ? 'Maintenance' : $pc['pct'] . '%' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">Tidak ada data komputer.</p>
    @endif

    {{-- Software installations --}}
    <h2 class="section">Instalasi Perangkat Lunak <span class="section-note">({{ count($installs) }} laporan)</span></h2>
    @if (count($installs) > 0)
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 110px;">Unit / PC</th>
                    <th>Perangkat Lunak</th>
                    <th style="width: 95px;">Reservasi</th>
                    <th class="num" style="width: 70px;">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($installs as $ins)
                    <tr>
                        <td>
                            @if ($ins['all_units'])
                                <span class="badge all">Semua unit</span>
                            @elseif (count($ins['units']) > 0)
                                @foreach ($ins['units'] as $unit)
                                    <span class="badge unit">{{ $unit }}</span>
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $ins['software'] }}</td>
                        <td>
                            <span class="mono"><b>{{ $ins['booking_code'] }}</b></span><br>
                            <span style="color: #8089a3; font-size: 8px;">{{ $ins['user'] }}</span>
                        </td>
                        <td class="num mono">{{ $ins['date']->translatedFormat('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">Tidak ada laporan instalasi perangkat lunak pada periode ini.</p>
    @endif

</body>
</html>
