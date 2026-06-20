<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingLogbookController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(! $booking->isEditable(), 403, 'Logbook untuk reservasi ini belum dapat diubah.');

        $validated = $request->validate([
            'checkpoint_progress' => ['required', 'string', 'min:10', 'max:2000'],
            'supervisor_name'     => ['nullable', 'string', 'max:255'],
            'related_course'      => ['nullable', 'string', 'max:255'],
        ], [
            'checkpoint_progress.required' => 'Deskripsi checkpoint wajib diisi.',
            'checkpoint_progress.min'      => 'Deskripsi minimal 10 karakter.',
        ]);

        // Preserve existing required category — if creating fresh, default to "lainnya"
        $existing = $booking->logbook;
        $payload  = $validated + [
            'category' => $existing->category ?? 'lainnya',
        ];

        $booking->logbook()->updateOrCreate(
            ['booking_id' => $booking->id],
            $payload
        );

        return redirect()
            ->route('booking.show', $booking)
            ->with('success', 'Logbook berhasil disimpan.');
    }
}
