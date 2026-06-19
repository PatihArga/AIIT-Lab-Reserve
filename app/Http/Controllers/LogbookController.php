<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'needs_installation'  => ['nullable', 'boolean'],
            'special_software'    => ['nullable', 'string', 'max:2000', 'required_if:needs_installation,1'],
        ], [
            'checkpoint_progress.required' => 'Catatan logbook wajib diisi.',
            'checkpoint_progress.min'      => 'Catatan minimal 10 karakter.',
            'checkpoint_progress.max'      => 'Catatan maksimal 2000 karakter.',
            'special_software.required_if' => 'Sebutkan perangkat lunak yang diunduh/diinstal.',
            'special_software.max'         => 'Daftar perangkat lunak maksimal 2000 karakter.',
        ]);

        $needsInstall = $request->boolean('needs_installation');

        // Snapshot tracked fields BEFORE saving so we can audit what actually changed.
        $existing = $booking->logbook;
        $old = [
            'checkpoint_progress' => $existing->checkpoint_progress ?? null,
            'needs_installation'  => $existing ? (bool) $existing->needs_installation : null,
            'special_software'    => $existing->special_software ?? null,
        ];

        $logbook = $booking->logbook()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'checkpoint_progress' => $validated['checkpoint_progress'],
                'needs_installation'  => $needsInstall,
                // When the box is off, don't keep a stale software list.
                'special_software'    => $needsInstall ? ($validated['special_software'] ?? null) : null,
                'category'            => $existing->category ?? 'lainnya',
            ],
        );

        $this->recordLogbookAudit($booking, $old, [
            'checkpoint_progress' => $logbook->checkpoint_progress,
            'needs_installation'  => (bool) $logbook->needs_installation,
            'special_software'    => $logbook->special_software,
        ]);

        return back()->with('success', 'Catatan logbook ' . $booking->booking_code . ' tersimpan.');
    }

    /**
     * Record a 'logbook.updated' audit entry, but only for the fields that
     * actually changed. checkpoint_progress is stored as a short preview to
     * keep audit rows compact.
     */
    private function recordLogbookAudit(Booking $booking, array $old, array $new): void
    {
        $changedOld = [];
        $changedNew = [];

        foreach ($new as $key => $newValue) {
            if (($old[$key] ?? null) === $newValue) {
                continue;
            }
            $changedOld[$key] = $this->auditPreview($key, $old[$key] ?? null);
            $changedNew[$key] = $this->auditPreview($key, $newValue);
        }

        if (empty($changedNew)) {
            return; // nothing changed → no audit noise
        }

        AuditLogService::record('logbook.updated', $booking, $changedOld, $changedNew);
    }

    /** Trim long text fields so the audit diff stays small. */
    private function auditPreview(string $key, $value)
    {
        if ($key === 'checkpoint_progress' && is_string($value)) {
            return Str::limit($value, 120);
        }

        return $value;
    }
}
