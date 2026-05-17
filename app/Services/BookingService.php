<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\BookingLogbook;
use App\Models\LabSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Statuses that "occupy" a slot for conflict purposes.
     */
    private const ACTIVE_STATUSES = ['submitted', 'under_review', 'approved'];

    /**
     * Check if a proposed booking conflicts with existing active bookings.
     *
     * Buffer is read from lab_settings (default 15 min) and applied on BOTH sides.
     * Conflict rules vary by booking_type — see PLAN-PHASE5.md §8.
     *
     * IMPORTANT: must be called inside a DB::transaction() so lockForUpdate() works.
     *
     * @param  string       $date              Y-m-d
     * @param  string       $startTime         H:i
     * @param  string       $endTime           H:i
     * @param  string       $bookingType       full_room | computers_only | room_only
     * @param  array        $computerIds       only used when bookingType = computers_only
     * @param  string|null  $roomSharing       exclusive | shared (only for room_only)
     * @param  int|null     $excludeBookingId  exclude this booking when re-checking
     */
    public function checkConflict(
        string $date,
        string $startTime,
        string $endTime,
        string $bookingType,
        array  $computerIds = [],
        ?string $roomSharing = null,
        ?int   $excludeBookingId = null,
    ): bool {
        $buffer    = (int) LabSetting::get('buffer_minutes', 15);
        $buffStart = Carbon::parse($startTime)->subMinutes($buffer)->format('H:i:s');
        $buffEnd   = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i:s');

        $base = Booking::query()
            ->where('date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('start_time', '<', $buffEnd)
            ->where('end_time',   '>', $buffStart)
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->lockForUpdate();

        if ($bookingType === 'full_room') {
            return $base->exists();
        }

        if ($bookingType === 'computers_only') {
            if ((clone $base)->where('booking_type', 'full_room')->exists()) {
                return true;
            }
            if (empty($computerIds)) {
                return false;
            }
            return (clone $base)
                ->where('booking_type', 'computers_only')
                ->whereHas('computers', fn($q) => $q->whereIn('computers.id', $computerIds))
                ->exists();
        }

        if ($bookingType === 'room_only') {
            if ((clone $base)->where('booking_type', 'full_room')->exists()) {
                return true;
            }

            if ($roomSharing === 'exclusive') {
                return (clone $base)->where('booking_type', 'room_only')->exists();
            }

            if ($roomSharing === 'shared') {
                return (clone $base)
                    ->where('booking_type', 'room_only')
                    ->where('room_sharing', 'exclusive')
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Create a booking + its logbook atomically.
     * Throws BookingConflictException if the slot is no longer free.
     *
     * @param  int    $userId
     * @param  array  $scheduleData  from session('booking_draft.schedule')
     * @param  array  $logbookData   from session('booking_draft.logbook')
     */
    public function createBooking(int $userId, array $scheduleData, array $logbookData): Booking
    {
        return DB::transaction(function () use ($userId, $scheduleData, $logbookData) {

            $conflict = $this->checkConflict(
                date:        $scheduleData['date'],
                startTime:   $scheduleData['start_time'],
                endTime:     $scheduleData['end_time'],
                bookingType: $scheduleData['type'],
                computerIds: $scheduleData['computers'] ?? [],
                roomSharing: $scheduleData['room_sharing'] ?? null,
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'Slot ini sudah terpesan atau bertabrakan dengan reservasi lain. Silakan pilih waktu lain.'
                );
            }

            $code = $this->generateBookingCode();

            $booking = Booking::create([
                'booking_code' => $code,
                'user_id'      => $userId,
                'booking_type' => $scheduleData['type'],
                'room_sharing' => $scheduleData['room_sharing'] ?? null,
                'date'         => $scheduleData['date'],
                'start_time'   => $scheduleData['start_time'],
                'end_time'     => $scheduleData['end_time'],
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            if ($scheduleData['type'] === 'computers_only' && !empty($scheduleData['computers'])) {
                $booking->computers()->attach($scheduleData['computers']);
            }

            BookingLogbook::create([
                'booking_id'          => $booking->id,
                'category'            => $logbookData['category'],
                'checkpoint_progress' => $logbookData['checkpoint_progress'],
                'related_course'      => $logbookData['related_course'] ?? null,
                'supervisor_name'     => $logbookData['supervisor_name'] ?? null,
                'needs_internet'      => (bool) ($logbookData['needs_internet'] ?? false),
            ]);

            return $booking;
        });
    }

    /**
     * Generate the next sequential booking code: LAB-NNNN
     * Must be called inside a transaction with lockForUpdate.
     */
    private function generateBookingCode(): string
    {
        $last = Booking::lockForUpdate()->max('booking_code');
        $next = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'LAB-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
