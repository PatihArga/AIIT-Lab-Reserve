<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingLogbook;
use App\Models\LabSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
     * @param  bool         $approvedOnly      when true, only 'approved' bookings count as conflicts.
     *                                          Default false → pending submissions also count, which is
     *                                          required for createBooking() to prevent double-submission
     *                                          of incompatible types (full_room, exclusive room_only).
     *                                          Read-only display APIs should pass true.
     */
    public function checkConflict(
        string $date,
        string $startTime,
        string $endTime,
        string $bookingType,
        array  $computerIds = [],
        ?string $roomSharing = null,
        ?int   $excludeBookingId = null,
        bool   $approvedOnly = false,
    ): bool {
        $statuses = $approvedOnly ? ['approved'] : self::ACTIVE_STATUSES;

        $buffer  = (int) LabSetting::get('buffer_minutes', 15);
        $buffEnd = Carbon::parse($endTime)->addMinutes($buffer)->format('H:i:s');

        $base = Booking::query()
            ->where('date', $date)
            ->whereIn('status', $statuses)
            ->where('start_time', '<', $buffEnd)
            // Strict: existing must end AFTER the new booking's actual start (no buffer here),
            // so adjacent bookings (existing.end == new.start) are allowed.
            ->where('end_time',   '>', $startTime)
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->lockForUpdate();

        if ($bookingType === 'full_room') {
            return $base->exists();
        }

        if ($bookingType === 'computers_only') {
            if ((clone $base)->where('booking_type', 'full_room')->exists()) {
                return true;
            }
            // An exclusive room_only booking takes the whole space — no PC use allowed inside it.
            if ((clone $base)
                    ->where('booking_type', 'room_only')
                    ->where('room_sharing', 'exclusive')
                    ->exists()) {
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
                if ((clone $base)->where('booking_type', 'room_only')->exists()) {
                    return true;
                }
                // Can't claim exclusive use of the room if computers are already booked in it.
                if ((clone $base)->where('booking_type', 'computers_only')->exists()) {
                    return true;
                }
                return false;
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

            // Attach selected PCs for computers_only (the booked units) and full_room
            // (display-only — which PCs the room booking will use). The conflict rules in
            // checkConflict() are unaffected: full_room still locks the entire room regardless.
            if (in_array($scheduleData['type'], ['computers_only', 'full_room'], true)
                && ! empty($scheduleData['computers'])) {
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

    /**
     * Auto-reject pending bookings that conflict with the just-approved one.
     * Called inside the same transaction as the approve(), so partial failures
     * cannot leave the system inconsistent.
     *
     * Uses a single overlap query + in-PHP type compatibility check to avoid
     * running lockForUpdate() in a loop (which could deadlock under load).
     */
    public function autoRejectConflicting(Booking $approved): void
    {
        $conflicting = Booking::where('id', '!=', $approved->id)
            ->whereIn('status', ['submitted', 'under_review'])
            ->where('date', $approved->date->format('Y-m-d'))
            ->where('start_time', '<', (string) $approved->end_time)
            ->where('end_time',   '>', (string) $approved->start_time)
            ->with('computers:id')
            ->get();

        foreach ($conflicting as $pending) {
            if (! $this->typesConflict($approved, $pending)) {
                continue;
            }

            $oldStatus = $pending->status;

            $pending->update([
                'status'      => 'rejected',
                'admin_notes' => 'Otomatis ditolak: reservasi '
                               . $approved->booking_code
                               . ' telah disetujui untuk slot yang sama.',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id'        => Auth::id(),
                'action'         => 'booking.auto_rejected',
                'auditable_type' => Booking::class,
                'auditable_id'   => $pending->id,
                'old_values'     => ['status' => $oldStatus],
                'new_values'     => ['status' => 'rejected'],
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
            ]);
        }
    }

    /**
     * Pure-PHP type compatibility check between two overlapping bookings.
     * Returns true if they cannot coexist regardless of timing.
     */
    private function typesConflict(Booking $a, Booking $b): bool
    {
        // full_room conflicts with everything
        if ($a->booking_type === 'full_room' || $b->booking_type === 'full_room') {
            return true;
        }

        // exclusive room_only conflicts with everything
        if ($a->booking_type === 'room_only' && $a->room_sharing === 'exclusive') {
            return true;
        }
        if ($b->booking_type === 'room_only' && $b->room_sharing === 'exclusive') {
            return true;
        }

        // computers_only vs computers_only: conflict only if PCs overlap
        if ($a->booking_type === 'computers_only' && $b->booking_type === 'computers_only') {
            return $a->computers->pluck('id')
                ->intersect($b->computers->pluck('id'))
                ->isNotEmpty();
        }

        // Remaining combos are compatible:
        //   - room_only shared + room_only shared
        //   - computers_only + room_only shared (room_only shared doesn't claim PCs)
        return false;
    }
}
