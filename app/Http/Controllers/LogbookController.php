<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogbookController extends Controller
{
    /** List the user's approved/completed reservations, each with its logbook note. */
    public function index(): View
    {
        $bookings = auth()->user()->bookings()
            ->whereIn('status', ['approved', 'completed'])
            ->with('logbook')
            ->orderByDesc('date')->orderByDesc('start_time')
            ->paginate(10);

        return view('logbook.index', compact('bookings'));
    }

    /** Save the free-text logbook note for one booking (owner only). */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(! $booking->isEditable(), 403, 'Logbook reservasi ini belum dapat diubah.');

        $validated = $request->validate([
            'checkpoint_progress' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'checkpoint_progress.required' => 'Catatan logbook wajib diisi.',
            'checkpoint_progress.min'      => 'Catatan minimal 10 karakter.',
            'checkpoint_progress.max'      => 'Catatan maksimal 2000 karakter.',
        ]);

        $booking->logbook()->updateOrCreate(
            ['booking_id' => $booking->id],
            $validated + ['category' => $booking->logbook->category ?? 'lainnya'],
        );

        return back()->with('success', 'Catatan logbook ' . $booking->booking_code . ' tersimpan.');
    }
}
