<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        [$from, $to, $period] = $this->resolveRange($request);

        $data = $this->reports->generate($from, $to);

        return view('admin.reports.index', [
            'report' => $data,
            'period' => $period,
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    /**
     * Server-side PDF export of the report for the selected range.
     * Reuses the exact same range resolution + data as the screen so the PDF
     * always matches what the admin is looking at.
     */
    public function exportPdf(Request $request): Response
    {
        [$from, $to, $period] = $this->resolveRange($request);

        $data = $this->reports->generate($from, $to);

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'report' => $data,
            'period' => $period,
            'from'   => $from,
            'to'     => $to,
        ])->setPaper('a4', 'portrait');

        $filename = 'Laporan AIIT - ' . now()->locale('id')->translatedFormat('d F Y') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Resolve the active date range from the request.
     * Priority: explicit from/to > period preset > default (current month).
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}  [from, to, period]
     */
    private function resolveRange(Request $request): array
    {
        $period = $request->input('period', 'month');

        if ($request->filled('from') && $request->filled('to')) {
            $from   = Carbon::parse($request->input('from'))->startOfDay();
            $to     = Carbon::parse($request->input('to'))->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to] = $this->resolvePeriod($period);
        }

        // Safety: don't allow inverted ranges
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to, $period];
    }

    /**
     * Resolve a named period preset into [from, to] Carbon instances.
     *
     * @return Carbon[]
     */
    private function resolvePeriod(string $period): array
    {
        // Cover the FULL natural span of each preset (matching 'week'), so
        // approved upcoming reservations later in the period are included
        // rather than cut off at "today".
        return match ($period) {
            'week'    => [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)],
            'quarter' => [now()->subMonths(2)->startOfMonth(), now()->endOfMonth()],
            'year'    => [now()->startOfYear(), now()->endOfYear()],
            default   => [now()->startOfMonth(), now()->endOfMonth()], // 'month'
        };
    }
}
