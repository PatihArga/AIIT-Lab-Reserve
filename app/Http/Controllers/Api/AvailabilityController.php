<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Computer;
use App\Models\LabSetting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /** GET /api/check-availability */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'         => ['required', 'date_format:Y-m-d'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'type'         => ['required', 'in:full_room,computers_only,room_only'],
            'computers'    => ['array'],
            'computers.*'  => ['integer'],
            'room_sharing' => ['nullable', 'in:exclusive,shared'],
        ]);

        // Run both conflict checks under a single transaction:
        //  - $hardConflict: only APPROVED bookings — the slot is definitively taken.
        //  - $pendingConflict: ANY active booking (incl. submitted/under_review). This mirrors
        //    createBooking() which uses approvedOnly=false. If this fires, submission will fail
        //    even though no approved booking blocks the slot — so we must report it as a conflict
        //    instead of a misleading "you can still submit" amber warning.
        // One transaction prevents a TOCTOU gap between the two checks and avoids
        // re-acquiring the same row locks twice per request.
        [$hardConflict, $pendingConflict] = DB::transaction(function () use ($validated) {
            $hard = $this->bookings->checkConflict(
                date:         $validated['date'],
                startTime:    $validated['start_time'],
                endTime:      $validated['end_time'],
                bookingType:  $validated['type'],
                computerIds:  $validated['computers'] ?? [],
                roomSharing:  $validated['room_sharing'] ?? null,
                approvedOnly: true,
            );

            $soft = ! $hard && $this->bookings->checkConflict(
                date:         $validated['date'],
                startTime:    $validated['start_time'],
                endTime:      $validated['end_time'],
                bookingType:  $validated['type'],
                computerIds:  $validated['computers'] ?? [],
                roomSharing:  $validated['room_sharing'] ?? null,
                approvedOnly: false,
            );

            return [$hard, $soft];
        });

        $conflict = $hardConflict || $pendingConflict;

        // hasPending: a competing pending request exists AND submission is still viable.
        // Only shown when neither hard nor pending conflict fires.
        $hasPending = ! $conflict && Booking::where('date', $validated['date'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time',   '>', $validated['start_time'])
            ->exists();

        return response()->json([
            'available' => ! $conflict,
            'pending'   => $hasPending,
            'message'   => $hardConflict
                ? 'Slot ini sudah disetujui untuk pengguna lain.'
                : ($pendingConflict
                    ? 'Slot ini bentrok dengan permintaan lain yang sedang ditinjau dan tidak kompatibel.'
                    : ($hasPending
                        ? 'Ada permintaan yang sedang ditinjau untuk slot ini. Anda tetap dapat mengajukan permintaan.'
                        : 'Slot tersedia.')),
        ]);
    }

    /** GET /api/computers/available */
    public function availableComputers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'       => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $buffer  = (int) LabSetting::get('buffer_minutes', 15);
        $buffEnd = Carbon::parse($validated['end_time'])->addMinutes($buffer)->format('H:i:s');

        // Single query for all active statuses; partition in PHP to avoid an extra DB round-trip.
        // Strict on end_time side so existing bookings ending exactly at the new
        // booking's start_time (adjacent, no overlap) are NOT treated as conflicts.
        $all = Booking::query()
            ->where('date', $validated['date'])
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->where('start_time', '<', $buffEnd)
            ->where('end_time',   '>', $validated['start_time'])
            ->with('computers')
            ->get();

        $approved = $all->where('status', 'approved');
        $pending  = $all->whereIn('status', ['submitted', 'under_review']);

        // Hard-blocked (approved only): full_room or exclusive room_only takes the whole lab.
        $hasFullRoom      = $approved->where('booking_type', 'full_room')->isNotEmpty();
        $hasExclusiveRoom = $approved
            ->where('booking_type', 'room_only')
            ->where('room_sharing', 'exclusive')
            ->isNotEmpty();
        $allHardBlocked = $hasFullRoom || $hasExclusiveRoom;

        $bookedIds = $allHardBlocked
            ? Computer::pluck('id')->toArray()
            : $approved
                ->where('booking_type', 'computers_only')
                ->flatMap(fn ($b) => $b->computers->pluck('id'))
                ->unique()
                ->values()
                ->toArray();

        // Soft-pending: another user has a submitted/under_review claim on these PCs.
        $hasPendingFullBlock = $pending->where('booking_type', 'full_room')->isNotEmpty()
            || $pending
                ->where('booking_type', 'room_only')
                ->where('room_sharing', 'exclusive')
                ->isNotEmpty();

        $pendingIds = $hasPendingFullBlock
            ? Computer::pluck('id')->toArray()
            : $pending
                ->where('booking_type', 'computers_only')
                ->flatMap(fn ($b) => $b->computers->pluck('id'))
                ->unique()
                ->values()
                ->toArray();

        $computers = Computer::orderBy('unit_number')
            ->get(['id', 'unit_number', 'label', 'status'])
            ->map(fn ($c) => [
                'id'        => $c->id,
                'label'     => $c->label,
                'status'    => $c->status,
                'available' => $c->status === 'online' && ! in_array($c->id, $bookedIds, true),
                'pending'   => $c->status === 'online'
                               && ! in_array($c->id, $bookedIds, true)
                               && in_array($c->id, $pendingIds, true),
            ]);

        // EC-H: any active computers_only booking in this slot blocks full_room and
        // room_only+exclusive from being chosen. The schedule page reads this to disable
        // those options proactively (the backend still enforces via checkConflict).
        $hasComputerBookings = $all->where('booking_type', 'computers_only')->isNotEmpty();

        return response()->json([
            'computers'             => $computers,
            'has_computer_bookings' => $hasComputerBookings,
        ]);
    }
}
