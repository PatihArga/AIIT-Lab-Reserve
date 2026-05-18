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

        $conflict = DB::transaction(fn () => $this->bookings->checkConflict(
            date:        $validated['date'],
            startTime:   $validated['start_time'],
            endTime:     $validated['end_time'],
            bookingType: $validated['type'],
            computerIds: $validated['computers'] ?? [],
            roomSharing: $validated['room_sharing'] ?? null,
        ));

        return response()->json([
            'available' => ! $conflict,
            'message'   => $conflict
                ? 'Slot ini sudah terpesan atau bertabrakan dengan reservasi lain.'
                : 'Slot tersedia.',
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

        $buffer    = (int) LabSetting::get('buffer_minutes', 15);
        $buffStart = Carbon::parse($validated['start_time'])->subMinutes($buffer)->format('H:i:s');
        $buffEnd   = Carbon::parse($validated['end_time'])->addMinutes($buffer)->format('H:i:s');

        // Bookings overlapping the requested window
        $overlapping = Booking::query()
            ->where('date', $validated['date'])
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->where('start_time', '<', $buffEnd)
            ->where('end_time',   '>', $buffStart)
            ->with('computers')
            ->get();

        // If any full_room OR exclusive room_only is active in the window → ALL computers are unavailable
        $hasFullRoom      = $overlapping->where('booking_type', 'full_room')->isNotEmpty();
        $hasExclusiveRoom = $overlapping
            ->where('booking_type', 'room_only')
            ->where('room_sharing', 'exclusive')
            ->isNotEmpty();

        $allBlocked = $hasFullRoom || $hasExclusiveRoom;

        $bookedIds = $allBlocked
            ? Computer::pluck('id')->toArray()
            : $overlapping
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
            ]);

        return response()->json(['computers' => $computers]);
    }
}
