<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        // ── Parse date range ──────────────────────────────────────
        // Priority: explicit from/to > period preset > default (current month)

        $period = $request->input('period', 'month');

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to   = Carbon::parse($request->input('to'))->endOfDay();
            // When explicit dates are used, mark period as 'custom'
            $period = 'custom';
        } else {
            [$from, $to] = $this->resolvePeriod($period);
        }

        // Safety: don't allow inverted ranges
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $data = $this->reports->generate($from, $to);

        return view('admin.reports.index', [
            'report'      => $data,
            'period'      => $period,
            'from'        => $from,
            'to'          => $to,
        ]);
    }

    /**
     * Resolve a named period preset into [from, to] Carbon instances.
     *
     * @return Carbon[]
     */
    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'week'    => [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)],
            'quarter' => [now()->subMonths(2)->startOfMonth(), now()->endOfDay()],
            'year'    => [now()->startOfYear(), now()->endOfDay()],
            default   => [now()->startOfMonth(), now()->endOfDay()], // 'month'
        };
    }
}
