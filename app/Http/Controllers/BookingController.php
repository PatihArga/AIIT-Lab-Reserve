<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Computer;
use App\Models\LabSetting;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────
    public function dashboard(): View
    {
        $user = auth()->user();

        $upcomingBookings = $user->bookings()
            ->with('computers')
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->where('date', '>=', today())
            ->orderBy('date')->orderBy('start_time')
            ->get();

        $completedBookings = $user->bookings()
            ->where('status', 'completed')
            ->latest('date')
            ->limit(5)
            ->get();

        $stats = [
            'upcoming_count'   => $upcomingBookings->count(),
            'this_month_total' => $user->bookings()
                ->whereMonth('date', now()->month)
                ->whereYear('date',  now()->year)
                ->count(),
            'pending_count' => $user->bookings()
                ->whereIn('status', ['submitted', 'under_review'])
                ->count(),
            'pending_code' => $user->bookings()
                ->whereIn('status', ['submitted', 'under_review'])
                ->oldest('submitted_at')
                ->value('booking_code'),
            'total_hours' => (int) round(
                $user->bookings()
                    ->whereIn('status', ['approved', 'completed'])
                    ->get()
                    ->sum(fn ($b) => Carbon::parse($b->start_time)
                        ->diffInMinutes(Carbon::parse($b->end_time)) / 60)
            ),
        ];

        // Lab settings for the right-panel info card
        $labSettings = [
            'operating_start'  => LabSetting::get('operating_start', '08:00'),
            'operating_end'    => LabSetting::get('operating_end', '22:00'),
            'operating_days'   => LabSetting::get('operating_days', '1,2,3,4,5,6'),
            'max_session_hours'=> LabSetting::get('max_session_hours', '4'),
            'buffer_minutes'   => LabSetting::get('buffer_minutes', '15'),
        ];

        // Computer status for the right-panel dots
        $computers = Computer::orderBy('unit_number')->get(['id', 'label', 'status']);

        return view('dashboard', compact(
            'upcomingBookings', 'completedBookings', 'stats', 'labSettings', 'computers'
        ));
    }

    // ── History & detail ──────────────────────────────────────────────────

    public function history(Request $request): View
    {
        $query = auth()->user()->bookings()
            ->with(['computers', 'logbook'])
            ->latest('date')->latest('start_time');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('q')) {
            $query->where('booking_code', 'like', '%' . $request->q . '%');
        }

        $bookings = $query->paginate(15)->withQueryString();

        return view('booking.history', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        $booking->load(['computers', 'logbook', 'reviewer']);
        return view('booking.show', compact('booking'));
    }

    // ── Cancel ────────────────────────────────────────────────────────────

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(! $booking->isCancellable(), 422, 'Reservasi tidak dapat dibatalkan.');

        $oldStatus = $booking->status;
        $booking->update(['status' => 'cancelled']);

        AuditLogService::record('booking.cancelled', $booking, ['status' => $oldStatus], ['status' => 'cancelled']);

        return redirect()
            ->route('booking.show', $booking)
            ->with('success', 'Reservasi ' . $booking->booking_code . ' telah dibatalkan.');
    }
}
