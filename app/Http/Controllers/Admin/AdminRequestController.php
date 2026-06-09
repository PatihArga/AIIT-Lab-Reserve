<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectRequestRequest;
use App\Models\Booking;
use App\Services\AuditLogService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminRequestController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    public function index(Request $request): View
    {
        $query = Booking::with(['user', 'user.teamAccount', 'logbook', 'computers'])
            ->latest('submitted_at');

        // C4 fix: the 'pending' tab maps to BOTH submitted + under_review.
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'pending') {
                $query->whereIn('status', ['submitted', 'under_review']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('booking_code', 'like', '%' . $term . '%')
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $term . '%'));
            });
        }

        $bookings     = $query->paginate(20)->withQueryString();
        $pendingCount = Booking::whereIn('status', ['submitted', 'under_review'])->count();

        return view('admin.requests.index', compact('bookings', 'pendingCount'));
    }

    public function show(Booking $booking): View|RedirectResponse
    {
        $booking->load(['user', 'user.teamAccount', 'user.studyProgram', 'computers', 'logbook', 'reviewer']);

        // S4 — auto-transition submitted → under_review when admin opens detail page.
        if ($booking->status === 'submitted') {
            $booking->update(['status' => 'under_review']);
            $booking->refresh();
        }

        // Live conflict check — must run inside transaction because checkConflict uses lockForUpdate.
        // approvedOnly:true → only flag conflicts against APPROVED bookings, since multiple
        // pending requests for the same slot are intentionally allowed now (admin picks the winner).
        $hasConflict = DB::transaction(fn () => $this->bookings->checkConflict(
            date:             $booking->date->format('Y-m-d'),
            startTime:        substr((string) $booking->start_time, 0, 5),
            endTime:          substr((string) $booking->end_time, 0, 5),
            bookingType:      $booking->booking_type,
            computerIds:      $booking->computers->pluck('id')->toArray(),
            roomSharing:      $booking->room_sharing,
            excludeBookingId: $booking->id,
            approvedOnly:     true,
        ));

        return view('admin.requests.show', compact('booking', 'hasConflict'));
    }

    public function approve(Booking $booking): RedirectResponse
    {
        abort_if(
            ! in_array($booking->status, ['submitted', 'under_review']),
            422,
            'Permintaan ini sudah diproses.'
        );

        // S8 — guard against approving past-date bookings.
        if ($booking->date->isPast()) {
            return back()->with('error', 'Tidak dapat menyetujui reservasi di tanggal lampau.');
        }

        $oldStatus = $booking->status;

        try {
            DB::transaction(function () use ($booking, $oldStatus) {
                $conflict = $this->bookings->checkConflict(
                    date:             $booking->date->format('Y-m-d'),
                    startTime:        substr((string) $booking->start_time, 0, 5),
                    endTime:          substr((string) $booking->end_time, 0, 5),
                    bookingType:      $booking->booking_type,
                    computerIds:      $booking->computers->pluck('id')->toArray(),
                    roomSharing:      $booking->room_sharing,
                    excludeBookingId: $booking->id,
                );

                if ($conflict) {
                    throw new BookingConflictException(
                        'Slot ini sekarang bentrok dengan reservasi lain. Persetujuan dibatalkan.'
                    );
                }

                $booking->update([
                    'status'      => 'approved',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'admin_notes' => null,
                ]);

                // C7 fix: use the actual previous status, not a hardcoded 'submitted'.
                AuditLogService::record('booking.approved', $booking, ['status' => $oldStatus], ['status' => 'approved']);

                // Auto-reject pending requests that conflict with this newly-approved booking.
                // MUST run inside this transaction so approve + auto-reject are atomic.
                $this->bookings->autoRejectConflicting($booking);
            });
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.requests.index')
            ->with('success', 'Reservasi ' . $booking->booking_code . ' telah disetujui.');
    }

    public function reject(RejectRequestRequest $request, Booking $booking): RedirectResponse
    {
        abort_if(
            ! in_array($booking->status, ['submitted', 'under_review']),
            422,
            'Permintaan ini sudah diproses.'
        );

        $oldStatus = $booking->status;

        $booking->update([
            'status'      => 'rejected',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogService::record('booking.rejected', $booking, ['status' => $oldStatus], ['status' => 'rejected', 'admin_notes' => $request->admin_notes]);

        return redirect()->route('admin.requests.index')
            ->with('success', 'Reservasi ' . $booking->booking_code . ' telah ditolak.');
    }

    public function complete(Booking $booking): RedirectResponse
    {
        abort_if(
            $booking->status !== 'approved',
            422,
            'Hanya reservasi yang disetujui yang dapat ditandai selesai.'
        );

        $booking->update(['status' => 'completed']);

        AuditLogService::record('booking.completed', $booking, ['status' => 'approved'], ['status' => 'completed']);

        return back()->with('success', 'Reservasi ' . $booking->booking_code . ' ditandai selesai.');
    }
}
