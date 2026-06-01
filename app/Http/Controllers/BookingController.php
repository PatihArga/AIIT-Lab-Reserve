<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

        return view('dashboard', compact(
            'upcomingBookings', 'completedBookings', 'stats'
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

        $booking->update(['status' => 'cancelled']);

        return redirect()
            ->route('booking.show', $booking)
            ->with('success', 'Reservasi ' . $booking->booking_code . ' telah dibatalkan.');
    }
}
