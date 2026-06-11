<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Computer;
use App\Models\LabSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Statuses considered "counted" for most metrics.
     * Submitted/under_review are included because they represent real demand.
     */
    private const COUNTED_STATUSES = ['submitted', 'under_review', 'approved', 'completed'];

    /**
     * Statuses that actually consumed lab time (for utilization / hours).
     */
    private const USED_STATUSES = ['approved', 'completed'];

    /**
     * Generate all report data for the given date range.
     *
     * @return array<string, mixed>
     */
    public function generate(Carbon $from, Carbon $to): array
    {
        return [
            'summary'      => $this->summary($from, $to),
            'weeklyUsage'  => $this->weeklyUsage($from, $to),
            'categories'   => $this->categoryBreakdown($from, $to),
            'topUsers'     => $this->topUsers($from, $to),
            'computerUsage'=> $this->computerUsage($from, $to),
        ];
    }

    // ─── Summary stats (4 hero cards) ─────────────────────────────

    private function summary(Carbon $from, Carbon $to): array
    {
        $baseQuery = Booking::whereBetween('date', [$from, $to]);

        // Total reservations (all counted statuses)
        $totalBookings = (clone $baseQuery)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->count();

        // Active users (distinct user_id with at least one counted booking)
        $activeUsers = (clone $baseQuery)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->distinct('user_id')
            ->count('user_id');

        // Booked hours (approved + completed only)
        $usedBookings = (clone $baseQuery)
            ->whereIn('status', self::USED_STATUSES)
            ->get(['start_time', 'end_time']);

        $totalUsedHours = $usedBookings->sum(fn ($b) => $this->durationHours($b->start_time, $b->end_time));

        // Average duration per session
        $avgDuration = $usedBookings->count() > 0
            ? round($totalUsedHours / $usedBookings->count(), 1)
            : 0;

        // Utilization rate: booked hours ÷ available hours
        $availableHours = $this->availableHoursInRange($from, $to);
        $utilization = $availableHours > 0
            ? round(($totalUsedHours / $availableHours) * 100)
            : 0;

        return [
            'total_bookings'  => $totalBookings,
            'utilization'     => $utilization,
            'available_hours' => $availableHours,
            'used_hours'      => round($totalUsedHours, 1),
            'active_users'    => $activeUsers,
            'avg_duration'    => $avgDuration,
        ];
    }

    // ─── Usage per week (bar chart) ───────────────────────────────

    private function weeklyUsage(Carbon $from, Carbon $to): array
    {
        $bookings = Booking::whereBetween('date', [$from, $to])
            ->whereIn('status', self::USED_STATUSES)
            ->get(['date', 'start_time', 'end_time']);

        // Group by ISO week number within the range
        $weeks = [];
        $weekIndex = 0;
        $cursor = $from->copy()->startOfWeek(Carbon::MONDAY);

        while ($cursor->lte($to)) {
            $weekStart = $cursor->copy();
            $weekEnd   = $cursor->copy()->endOfWeek(Carbon::SUNDAY);

            // Clamp to the actual from/to range
            $effectiveStart = $weekStart->lt($from) ? $from->copy() : $weekStart;
            $effectiveEnd   = $weekEnd->gt($to) ? $to->copy() : $weekEnd;

            $weekIndex++;
            $hours = $bookings
                ->filter(fn ($b) => $b->date->between($effectiveStart, $effectiveEnd))
                ->sum(fn ($b) => $this->durationHours($b->start_time, $b->end_time));

            $weeks[] = [
                'label' => 'W' . $weekIndex,
                'value' => round($hours, 1),
                'range' => $effectiveStart->format('d/m') . '–' . $effectiveEnd->format('d/m'),
            ];

            $cursor->addWeek();
        }

        // Compute max for bar scaling (avoid 0)
        $maxVal = max(1, ...array_column($weeks, 'value'));
        foreach ($weeks as &$w) {
            $w['max'] = $maxVal;
        }

        return $weeks;
    }

    // ─── Category breakdown ───────────────────────────────────────

    private function categoryBreakdown(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('bookings')
            ->join('booking_logbooks', 'bookings.id', '=', 'booking_logbooks.booking_id')
            ->whereBetween('bookings.date', [$from, $to])
            ->whereIn('bookings.status', self::COUNTED_STATUSES)
            ->select('booking_logbooks.category', DB::raw('COUNT(*) as count'))
            ->groupBy('booking_logbooks.category')
            ->orderByDesc('count')
            ->get();

        $total = $rows->sum('count') ?: 1; // avoid division by zero

        // Color assignments by category (cycle if more than palette)
        $colors = ['bg-ink-700', 'bg-mark-500', 'bg-[#2eb8a0]', 'bg-status-review', 'bg-rule-strong'];

        return $rows->values()->map(fn ($row, $i) => [
            'label' => $row->category ?: 'Tanpa Kategori',
            'count' => $row->count,
            'pct'   => round(($row->count / $total) * 100),
            'color' => $colors[$i % count($colors)],
        ])->toArray();
    }

    // ─── Top 5 most active users ──────────────────────────────────

    private function topUsers(Carbon $from, Carbon $to): array
    {
        return DB::table('bookings')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->leftJoin('teams', 'users.id', '=', 'teams.user_id')
            ->whereBetween('bookings.date', [$from, $to])
            ->whereIn('bookings.status', self::COUNTED_STATUSES)
            ->select(
                'users.id',
                'users.name',
                'users.role',
                'teams.name as team_name',
                DB::raw('COUNT(*) as booking_count'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, bookings.start_time, bookings.end_time)) as total_minutes'),
            )
            ->groupBy('users.id', 'users.name', 'users.role', 'teams.name')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'name'  => $u->role === 'team' && $u->team_name ? $u->team_name : $u->name,
                'role'  => match ($u->role) {
                    'team'     => 'Tim',
                    'lecturer' => 'Dosen',
                    default    => ucfirst($u->role),
                },
                'count' => $u->booking_count,
                'hours' => round(($u->total_minutes ?? 0) / 60, 1),
            ])
            ->toArray();
    }

    // ─── Per-computer usage ───────────────────────────────────────

    private function computerUsage(Carbon $from, Carbon $to): array
    {
        $computers = Computer::orderBy('unit_number')->get(['id', 'label', 'status']);
        $availableHoursPerUnit = $this->availableHoursPerUnit($from, $to);

        // Get booked minutes per computer (approved + completed only)
        $pcMinutes = DB::table('booking_computers')
            ->join('bookings', 'booking_computers.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.date', [$from, $to])
            ->whereIn('bookings.status', self::USED_STATUSES)
            ->select(
                'booking_computers.computer_id',
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, bookings.start_time, bookings.end_time)) as total_minutes'),
            )
            ->groupBy('booking_computers.computer_id')
            ->pluck('total_minutes', 'computer_id');

        // Also add hours from full_room bookings (they implicitly use ALL computers)
        $fullRoomMinutes = DB::table('bookings')
            ->whereBetween('date', [$from, $to])
            ->whereIn('status', self::USED_STATUSES)
            ->where('booking_type', 'full_room')
            ->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes'))
            ->value('total_minutes') ?? 0;

        return $computers->map(function ($c) use ($pcMinutes, $fullRoomMinutes, $availableHoursPerUnit) {
            $isMaint = $c->status !== 'online';
            $minutes = ($pcMinutes[$c->id] ?? 0) + $fullRoomMinutes;
            $hours   = round($minutes / 60, 1);
            $pct     = $availableHoursPerUnit > 0
                ? min(100, round(($hours / $availableHoursPerUnit) * 100))
                : 0;

            return [
                'label'       => $c->label,
                'hours'       => $hours,
                'pct'         => $isMaint ? 0 : $pct,
                'maintenance' => $isMaint,
            ];
        })->toArray();
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Duration in hours between two time strings (H:i:s or H:i).
     */
    private function durationHours(string $start, string $end): float
    {
        $s = Carbon::parse($start);
        $e = Carbon::parse($end);

        return max(0, $s->diffInMinutes($e) / 60);
    }

    /**
     * Total available lab hours in the date range (all units combined).
     * Available hours = operating days in range × daily operating hours × 9 computers.
     */
    private function availableHoursInRange(Carbon $from, Carbon $to): float
    {
        return $this->availableHoursPerUnit($from, $to) * Computer::where('status', 'online')->count();
    }

    /**
     * Available lab hours per single unit in the date range.
     */
    private function availableHoursPerUnit(Carbon $from, Carbon $to): float
    {
        $operatingDays  = $this->operatingDays();
        $operatingStart = (int) LabSetting::get('operating_start', '08:00');
        $operatingEnd   = (int) LabSetting::get('operating_end', '22:00');
        $dailyHours     = max(0, $operatingEnd - $operatingStart);

        // Count operating days in the range
        $count = 0;
        $period = CarbonPeriod::create($from, $to);
        foreach ($period as $date) {
            if (in_array($date->dayOfWeek, $operatingDays, true)) {
                $count++;
            }
        }

        return $count * $dailyHours;
    }

    /**
     * Parse operating_days CSV from lab_settings into an array of day-of-week ints.
     * Default: Mon–Sat (1,2,3,4,5,6). Sunday = 0.
     */
    private function operatingDays(): array
    {
        $csv = LabSetting::get('operating_days', '1,2,3,4,5,6');

        return array_map('intval', explode(',', $csv));
    }
}
