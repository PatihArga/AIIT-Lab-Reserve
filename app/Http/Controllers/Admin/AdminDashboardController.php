<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Computer;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'pending_count'       => Booking::whereIn('status', ['submitted', 'under_review'])->count(),
            'approved_this_month' => Booking::where('status', 'approved')
                ->whereMonth('reviewed_at', now()->month)
                ->whereYear('reviewed_at', now()->year)
                ->count(),
            'computers_online'    => Computer::where('status', 'online')->count(),
            'computers_total'     => Computer::count(),
        ];

        $pendingBookings = Booking::with(['user', 'user.teamAccount', 'logbook'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('submitted_at')
            ->limit(5)
            ->get();

        $recentActivity = Booking::with(['user', 'reviewer'])
            ->whereIn('status', ['approved', 'rejected', 'completed'])
            ->whereNotNull('reviewed_at')
            ->latest('reviewed_at')
            ->limit(4)
            ->get();

        $computers = Computer::orderBy('unit_number')->get();

        return view('admin.dashboard', compact('stats', 'pendingBookings', 'recentActivity', 'computers'));
    }
}
