{{--
    Server-side PDF template for Audit Log (dompdf).
    Standalone document — NO x-app-layout, NO Tailwind. Table layout + internal
    CSS only. $logs is a flat collection of presented rows (see controller
    present()); grouping by day is done here.
--}}
@php
    $grouped = collect($logs)->groupBy('date_key');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Audit Log Lab AIIT UKRIDA</title>
    <style>
        @page { margin: 26px 32px 44px 32px; }

        * { font-family: 'DejaVu Sans', sans-serif; }

        body { margin: 0; color: #1a2236; font-size: 9px; line-height: 1.4; }

        .doc-header { background-color: #0A1A47; color: #ffffff; padding: 16px 18px; border-radius: 6px; }
        .doc-header .eyebrow { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: #aab4d4; margin: 0 0 4px 0; }
        .doc-header h1 { font-size: 17px; margin: 0; font-weight: bold; }
        .doc-header .meta { font-size: 9px; color: #cfd6ea; margin-top: 6px; }

        /* KPI stats */
        table.kpi { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-top: 10px; }
        table.kpi td { width: 25%; background-color: #f7f8fc; border: 1px solid #e4e8f3; border-radius: 5px; padding: 9px 11px; vertical-align: top; }
        .kpi-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #818aa6; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #0A1A47; margin-top: 4px; }

        .mono { font-family: 'DejaVu Sans Mono', monospace; }

        /* Log table */
        table.log { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.log th { background-color: #f1f3f9; color: #515b78; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 5px 7px; border-bottom: 1px solid #d7dceb; }
        table.log td { padding: 5px 7px; border-bottom: 1px solid #eceef5; font-size: 9px; vertical-align: top; }

        tr.daygroup td { background-color: #0A1A47; color: #ffffff; font-size: 8.5px; font-weight: bold; padding: 5px 8px; letter-spacing: 0.5px; }
        tr.daygroup td .count { color: #aab4d4; font-weight: normal; }

        .tag { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7.5px; font-weight: bold; font-family: 'DejaVu Sans Mono', monospace; }
        .desc { font-weight: bold; color: #16203a; }
        .target { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; background-color: #eef1f8; color: #44507a; padding: 1px 4px; border-radius: 3px; }

        .diff-before { color: #b4232f; text-decoration: line-through; }
        .diff-arrow { color: #9aa2b8; }
        .diff-after { color: #167a3c; font-weight: bold; }
        .diff-label { color: #6b7493; }
        .muted { color: #9aa2b8; }

        .empty { font-size: 10px; color: #97a0b8; padding: 24px 0; text-align: center; font-style: italic; }

        .foot { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 7.5px; color: #9aa2b8; }
        .foot .r { text-align: right; }
    </style>
</head>
<body>

    <table class="foot"><tr>
        <td>Lab AIIT UKRIDA — Audit Log Sistem</td>
        <td class="r">Dicetak {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td>
    </tr></table>

    <div class="doc-header">
        <p class="eyebrow">Sistem</p>
        <h1>Audit Log Lab AIIT UKRIDA</h1>
        <div class="meta">Riwayat aktivitas sistem &nbsp;·&nbsp; {{ $filterSummary }}</div>
    </div>

    {{-- Stats (system-wide, mirror the screen header) --}}
    <table class="kpi">
        <tr>
            <td>
                <div class="kpi-label">Total Aktivitas</div>
                <div class="kpi-value">{{ $stats['total'] }}</div>
            </td>
            <td>
                <div class="kpi-label">Hari Ini</div>
                <div class="kpi-value">{{ $stats['today'] }}</div>
            </td>
            <td>
                <div class="kpi-label">Reservasi Diproses</div>
                <div class="kpi-value">{{ $stats['processed'] }}</div>
            </td>
            <td>
                <div class="kpi-label">Pengguna Aktif</div>
                <div class="kpi-value">{{ $stats['active_users'] }}</div>
            </td>
        </tr>
    </table>

    @if (collect($logs)->isEmpty())
        <p class="empty">Tidak ada entri yang cocok dengan filter.</p>
    @else
        <table class="log">
            <thead>
                <tr>
                    <th style="width: 34px;">Waktu</th>
                    <th style="width: 150px;">Aktivitas</th>
                    <th style="width: 80px;">Oleh</th>
                    <th style="width: 65px;">Target</th>
                    <th>Perubahan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grouped as $dateKey => $entries)
                    @php $first = $entries->first(); @endphp
                    <tr class="daygroup">
                        <td colspan="5">
                            {{ $first['day_label'] }}
                            @if ($first['day_rel'] !== '')
                                · {{ $first['day_rel'] }}
                            @endif
                            <span class="count">&nbsp;— {{ $entries->count() }} aktivitas</span>
                        </td>
                    </tr>
                    @foreach ($entries as $log)
                        <tr>
                            <td class="mono">{{ $log['time'] }}</td>
                            <td>
                                <div class="desc">{{ $log['desc'] }}</div>
                                <span class="tag" style="color: {{ $log['color'] }};">{{ $log['action'] }}</span>
                            </td>
                            <td>{{ $log['user'] }}</td>
                            <td>
                                @if ($log['target'] !== '—')
                                    <span class="target">{{ $log['target'] }}</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($log['inline_diff'])
                                    <span class="diff-before">{{ $log['inline_diff']['before'] }}</span>
                                    <span class="diff-arrow">→</span>
                                    <span class="diff-after">{{ $log['inline_diff']['after'] }}</span>
                                @elseif (! empty($log['changes']))
                                    @foreach ($log['changes'] as $ch)
                                        <div style="margin-bottom: 2px;">
                                            <span class="diff-label">{{ $ch['label'] }}:</span>
                                            <span class="diff-before">{{ $ch['before'] }}</span>
                                            <span class="diff-arrow">→</span>
                                            <span class="diff-after">{{ $ch['after'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
