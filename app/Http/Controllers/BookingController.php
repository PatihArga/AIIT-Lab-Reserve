<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Http\Requests\BookingStoreRequest;
use App\Models\Booking;
use App\Models\Computer;
use App\Models\LabSetting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

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

        $calendarEvents = $user->bookings()
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->whereMonth('date', now()->month)
            ->whereYear('date',  now()->year)
            ->get(['date', 'start_time', 'end_time'])
            ->groupBy(fn ($b) => (int) $b->date->day)
            ->map(fn ($group) => $group
                ->flatMap(function ($b) {
                    $start = (int) Carbon::parse($b->start_time)->hour;
                    $end   = (int) Carbon::parse($b->end_time)->hour;
                    return range($start, max($start, $end - 1));
                })
                ->unique()->sort()->values()
            );

        return view('dashboard', compact(
            'upcomingBookings', 'completedBookings', 'stats', 'calendarEvents'
        ));
    }

    // ── Booking creation flow ─────────────────────────────────────────────

    /** Step 1: schedule (type + date + time + computers) */
    public function showSchedule(Request $request): View|RedirectResponse
    {
        // If the dashboard modal navigated here with prefill params, seed the session draft
        // and redirect to the clean URL so the form renders without query params.
        if ($request->hasAny(['type', 'date', 'start_time', 'end_time'])) {
            $typeMap = ['computer' => 'computers_only', 'both' => 'full_room', 'room' => 'room_only'];
            $rawType = $request->input('type', '');
            $prefill = [
                'type'         => $typeMap[$rawType] ?? $rawType,
                'date'         => $request->input('date', ''),
                'start_time'   => $request->input('start_time', ''),
                'end_time'     => $request->input('end_time', ''),
                'room_sharing' => $request->input('room_sharing'),
                'computers'    => array_map('intval', (array) $request->input('computers', [])),
            ];
            session(['booking_draft.schedule' => $prefill]);
            return redirect()->route('booking.schedule');
        }

        $computers = Computer::orderBy('unit_number')->get(['id', 'unit_number', 'label', 'status']);
        $draft     = session('booking_draft.schedule');
        return view('booking.schedule', compact('computers', 'draft'));
    }

    /** Step 1 → 2 handler: validates step 1 data, stores in session, renders logbook view */
    public function showLogbook(Request $request): View|RedirectResponse
    {
        // If user navigates directly without query params AND no draft exists, redirect to step 1.
        if (! $request->hasAny(['type', 'date', 'start_time', 'end_time'])
            && ! session()->has('booking_draft.schedule')) {
            return redirect()->route('booking.schedule');
        }

        // If query params are present, validate & re-store. Otherwise render with existing session draft.
        if ($request->hasAny(['type', 'date', 'start_time', 'end_time'])) {
            try {
                $validated = $this->validateSchedule($request);
            } catch (ValidationException $e) {
                return redirect()->route('booking.schedule')
                    ->withErrors($e->errors())
                    ->withInput();
            }
            session(['booking_draft.schedule' => $validated]);
        }

        $logbookDraft = session('booking_draft.logbook', []);
        return view('booking.logbook', ['logbookDraft' => $logbookDraft]);
    }

    /** Step 2 → 3 handler: validates step 2 data, stores in session, renders review view */
    public function showReview(Request $request): View|RedirectResponse
    {
        if (! session()->has('booking_draft.schedule')) {
            return redirect()->route('booking.schedule')
                ->with('error', 'Mulai dari awal — data sesi telah hilang.');
        }

        if ($request->hasAny(['category', 'checkpoint_progress'])) {
            try {
                $validated = $this->validateLogbook($request);
            } catch (ValidationException $e) {
                return redirect()->route('booking.logbook')
                    ->withErrors($e->errors())
                    ->withInput();
            }
            session(['booking_draft.logbook' => $validated]);
        }

        if (! session()->has('booking_draft.logbook')) {
            return redirect()->route('booking.logbook');
        }

        $draft = session('booking_draft');

        // Resolve computer labels for display
        $computerLabels = [];
        if (! empty($draft['schedule']['computers'])) {
            $computerLabels = Computer::whereIn('id', $draft['schedule']['computers'])
                ->orderBy('unit_number')
                ->pluck('label')
                ->toArray();
        }

        return view('booking.review', compact('draft', 'computerLabels'));
    }

    /** Final POST — create booking from session */
    public function store(BookingStoreRequest $request): RedirectResponse
    {
        $schedule = session('booking_draft.schedule');
        $logbook  = session('booking_draft.logbook');

        if (! $schedule || ! $logbook) {
            return redirect()->route('booking.schedule')
                ->with('error', 'Data sesi tidak lengkap — silakan ulangi.');
        }

        try {
            $booking = $this->bookings->createBooking(auth()->id(), $schedule, $logbook);
        } catch (BookingConflictException $e) {
            return redirect()->route('booking.schedule')
                ->with('error', $e->getMessage());
        }

        session()->forget('booking_draft');

        return redirect()
            ->route('booking.show', $booking)
            ->with('success', 'Permintaan reservasi ' . $booking->booking_code . ' berhasil dikirim.');
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

    // ── Validation helpers ────────────────────────────────────────────────

    /**
     * Validate Step 1 (schedule) fields PLUS business rules.
     * Returns normalised array ready to be stored in session.
     */
    private function validateSchedule(Request $request): array
    {
        $computers = Computer::pluck('id')->toArray();

        $rules = [
            'type'         => ['required', Rule::in(['full_room', 'computers_only', 'room_only'])],
            'room_sharing' => ['nullable', 'required_if:type,room_only', Rule::in(['exclusive', 'shared'])],
            'date'         => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'computers'    => ['array', 'required_if:type,computers_only'],
            'computers.*'  => ['integer', Rule::in($computers)],
        ];

        $validated = $request->validate($rules, [
            'date.after_or_equal'      => 'Tanggal tidak boleh di masa lalu.',
            'end_time.after'           => 'Waktu selesai harus setelah waktu mulai.',
            'computers.required_if'    => 'Pilih minimal 1 unit komputer.',
            'room_sharing.required_if' => 'Pilih mode penggunaan ruang (Eksklusif atau Berbagi).',
        ]);

        // Business rules
        $errors = [];

        // Operating day check
        $dayOfWeek    = Carbon::parse($validated['date'])->dayOfWeekIso; // 1=Mon ... 7=Sun
        $operatingRaw = (string) LabSetting::get('operating_days', '1,2,3,4,5,6');
        $allowedDays  = array_map('intval', array_filter(explode(',', $operatingRaw)));
        if (! in_array($dayOfWeek, $allowedDays, true)) {
            $errors['date'] = 'Lab tidak beroperasi pada hari tersebut.';
        }

        // Operating hours check
        $opStart = LabSetting::get('operating_start', '08:00');
        $opEnd   = LabSetting::get('operating_end',   '22:00');
        if ($validated['start_time'] < $opStart) {
            $errors['start_time'] = 'Waktu mulai sebelum jam buka lab (' . $opStart . ').';
        }
        if ($validated['end_time'] > $opEnd) {
            $errors['end_time'] = 'Waktu selesai melewati jam tutup lab (' . $opEnd . ').';
        }

        // Max duration
        $maxHours = (float) LabSetting::get('max_session_hours', 4);
        $duration = Carbon::parse($validated['start_time'])
            ->diffInMinutes(Carbon::parse($validated['end_time'])) / 60;
        if ($duration > $maxHours) {
            $errors['end_time'] = 'Durasi melebihi batas maksimum (' . $maxHours . ' jam).';
        }

        // Computer status check (selected units must be online)
        if (! empty($validated['computers'])) {
            $invalidIds = Computer::whereIn('id', $validated['computers'])
                ->where('status', '!=', 'online')
                ->pluck('label')
                ->toArray();
            if (! empty($invalidIds)) {
                $errors['computers'] = 'Beberapa unit tidak tersedia: ' . implode(', ', $invalidIds);
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Normalise: full_room implicitly uses all online computers (we don't store them as pivots — full_room blocks the whole lab)
        // For computers_only, computers[] is required
        // For room_only, computers should be empty
        if ($validated['type'] !== 'computers_only') {
            $validated['computers'] = [];
        }
        if ($validated['type'] !== 'room_only') {
            $validated['room_sharing'] = null;
        }

        return $validated;
    }

    /** Validate Step 2 (logbook) fields. */
    private function validateLogbook(Request $request): array
    {
        $validated = $request->validate([
            'category'            => ['required', Rule::in([
                'penelitian', 'project_akademik', 'praktikum', 'tugas_akhir', 'lainnya',
            ])],
            'checkpoint_progress' => ['required', 'string', 'min:10', 'max:2000'],
            'related_course'      => ['required', 'string', 'max:255'],
            'supervisor_name'     => ['nullable', 'string', 'max:255'],
            'needs_internet'      => ['nullable'],
        ], [
            'checkpoint_progress.required' => 'Alasan/checkpoint kegiatan wajib diisi.',
            'checkpoint_progress.min'      => 'Deskripsi minimal 10 karakter.',
        ]);

        // Normalize checkbox value (HTML sends "1" when checked, missing when unchecked)
        $validated['needs_internet'] = (bool) ($validated['needs_internet'] ?? false);

        return $validated;
    }
}
