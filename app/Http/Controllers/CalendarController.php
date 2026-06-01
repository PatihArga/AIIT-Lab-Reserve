<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\Computer;
use App\Models\LabSetting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /**
     * Week-view calendar page. Loads all active bookings for a 5-week window
     * (1 week past + current + 3 future) and maps them to a flat, JS-friendly
     * shape consumed by the Alpine `weekCal()` component.
     */
    public function index(): View
    {
        $calStart = now()->startOfWeek(Carbon::MONDAY)->subWeeks(1);
        $calEnd   = $calStart->copy()->addWeeks(5)->endOfWeek(Carbon::SUNDAY);

        $calendarEvents = Booking::with(['user:id,name', 'computers:id,label,unit_number'])
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->whereBetween('date', [$calStart->toDateString(), $calEnd->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->map(fn (Booking $b) => $this->toCalEvent($b))
            ->values()
            ->toArray();

        return view('calendar.index', [
            'calendarEvents' => $calendarEvents,
            'todayIso'       => now()->toDateString(),
            'loadStartIso'   => $calStart->toDateString(),
            'loadEndIso'     => $calEnd->toDateString(),
        ]);
    }

    /**
     * Map a Booking to the calendar event shape:
     * { id, date, start(min), dur(min), type, label, who, status, booking_code, is_mine }
     *
     * Type mapping:
     *   computers_only            → computer       (indigo)
     *   full_room                 → room_computer  (violet)
     *   room_only + exclusive     → room_exclusive (teal)
     *   room_only + shared        → room_sharing   (amber)
     */
    private function toCalEvent(Booking $b): array
    {
        $startMin = $this->timeToMin($b->start_time);
        $endMin   = $this->timeToMin($b->end_time);

        if ($b->booking_type === 'computers_only') {
            $type   = 'computer';
            $labels = $b->computers->pluck('label')->all();
            $label  = match (true) {
                count($labels) === 1 => $labels[0],
                count($labels) > 1   => count($labels) . ' unit',
                default              => 'Komputer',
            };
        } elseif ($b->booking_type === 'full_room') {
            $type   = 'room_computer';
            $labels = $b->computers->pluck('label')->all();
            $label  = count($labels) ? implode(', ', $labels) : 'Seluruh Lab';
        } elseif ($b->room_sharing === 'exclusive') {
            $type  = 'room_exclusive';
            $label = 'Ruang (Eksklusif)';
        } else {
            $type  = 'room_sharing';
            $label = 'Ruang (Berbagi)';
        }

        return [
            'id'           => $b->id,
            'date'         => $b->date->format('Y-m-d'),
            'start'        => $startMin,
            'dur'          => max($endMin - $startMin, 30),
            'type'         => $type,
            'label'        => $label,
            'who'          => $b->user->name ?? '—',
            'status'       => $b->status,
            'booking_code' => $b->booking_code,
            'is_mine'      => $b->user_id === auth()->id(),
        ];
    }

    /**
     * Create a booking from the inline calendar popover form (single POST).
     * Replaces the old multi-step schedule → logbook → review flow.
     *
     * Type mapping from the popover:
     *   "Computer only"   → computers_only (exactly 1 PC)
     *   "Room + Computer" → full_room      (1+ PCs attached for display; whole room is reserved)
     *   "Room only"       → room_only      (exclusive | shared)
     */
    public function store(Request $request): RedirectResponse
    {
        $computerIds = Computer::pluck('id')->toArray();

        $validator = Validator::make($request->all(), [
            'booking_type' => ['required', Rule::in(['full_room', 'computers_only', 'room_only'])],
            'room_sharing' => ['nullable', 'required_if:booking_type,room_only', Rule::in(['exclusive', 'shared'])],
            'date'         => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'computers'    => ['array', 'required_if:booking_type,computers_only'],
            'computers.*'  => ['integer', Rule::in($computerIds)],
            'reason'       => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'date.after_or_equal'      => 'Tanggal tidak boleh di masa lalu.',
            'end_time.after'           => 'Waktu selesai harus setelah waktu mulai.',
            'computers.required_if'    => 'Pilih unit komputer.',
            'room_sharing.required_if' => 'Pilih mode penggunaan ruang (Eksklusif atau Berbagi).',
            'reason.required'          => 'Alasan / tujuan reservasi wajib diisi.',
            'reason.min'               => 'Alasan minimal 3 karakter.',
        ]);

        $validator->after(fn ($v) => $this->validateBusinessRules($request, $v));

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $data = $validator->validated();
        $type = $data['booking_type'];

        // Normalise computers per type:
        //   computers_only → exactly 1 (the UI enforces single-select; clamp defensively)
        //   full_room      → 0+ (attached for display only; conflict still locks whole room)
        //   room_only      → none
        $computers = match ($type) {
            'computers_only' => array_slice(array_map('intval', $data['computers'] ?? []), 0, 1),
            'full_room'      => array_map('intval', $data['computers'] ?? []),
            default          => [],
        };

        $schedule = [
            'type'         => $type,
            'date'         => $data['date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'room_sharing' => $type === 'room_only' ? $data['room_sharing'] : null,
            'computers'    => $computers,
        ];

        // The single "reason" field maps to the logbook's checkpoint_progress; the remaining
        // logbook fields take safe defaults and can be filled in after approval via the
        // existing logbook edit form (BookingLogbookController).
        $logbook = [
            'category'            => 'lainnya',
            'checkpoint_progress' => $data['reason'],
            'related_course'      => null,
            'supervisor_name'     => null,
            'needs_internet'      => false,
        ];

        try {
            $booking = $this->bookings->createBooking(auth()->id(), $schedule, $logbook);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('booking.show', $booking)
            ->with('success', 'Reservasi ' . $booking->booking_code . ' berhasil dikirim.');
    }

    /**
     * Lab business rules (operating day/hours, max session length, PC status).
     * Mirrors the rules previously enforced in BookingController::validateSchedule().
     * Errors are attached to the validator so they surface as a single flash message.
     */
    private function validateBusinessRules(Request $request, $validator): void
    {
        $date  = $request->input('date');
        $start = $request->input('start_time');
        $end   = $request->input('end_time');

        if (! $date || ! $start || ! $end) {
            return; // base rules already failed; nothing to add
        }

        // Operating day
        try {
            $dayOfWeek = Carbon::parse($date)->dayOfWeekIso; // 1=Mon ... 7=Sun
        } catch (\Throwable $e) {
            return;
        }
        $operatingRaw = (string) LabSetting::get('operating_days', '1,2,3,4,5,6');
        $allowedDays  = array_map('intval', array_filter(explode(',', $operatingRaw)));
        if (! in_array($dayOfWeek, $allowedDays, true)) {
            $validator->errors()->add('date', 'Lab tidak beroperasi pada hari tersebut.');
        }

        // Operating hours
        $opStart = LabSetting::get('operating_start', '08:00');
        $opEnd   = LabSetting::get('operating_end', '22:00');
        if ($start < $opStart) {
            $validator->errors()->add('start_time', 'Waktu mulai sebelum jam buka lab (' . $opStart . ').');
        }
        if ($end > $opEnd) {
            $validator->errors()->add('end_time', 'Waktu selesai melewati jam tutup lab (' . $opEnd . ').');
        }

        // Max session duration
        $maxHours = (float) LabSetting::get('max_session_hours', 4);
        try {
            $duration = Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60;
            if ($duration > $maxHours) {
                $validator->errors()->add('end_time', 'Durasi melebihi batas maksimum (' . $maxHours . ' jam).');
            }
        } catch (\Throwable $e) {
            // malformed times already caught by base rules
        }

        // Selected computers must be online
        $computers = array_filter((array) $request->input('computers', []));
        if (! empty($computers)) {
            $invalid = Computer::whereIn('id', $computers)
                ->where('status', '!=', 'online')
                ->pluck('label')
                ->toArray();
            if (! empty($invalid)) {
                $validator->errors()->add('computers', 'Beberapa unit tidak tersedia: ' . implode(', ', $invalid));
            }
        }
    }

    private function timeToMin(string $time): int
    {
        $parts = explode(':', substr($time, 0, 5));

        return (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
    }
}
