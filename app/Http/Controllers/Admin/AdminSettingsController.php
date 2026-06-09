<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    /** Settings keys this screen manages (Google Calendar = Phase 7, excluded). */
    private const KEYS = [
        'lab_name', 'admin_email', 'operating_start', 'operating_end',
        'operating_days', 'max_session_hours', 'buffer_minutes',
    ];

    public function index(): View
    {
        $settings = [
            'lab_name'          => LabSetting::get('lab_name', ''),
            'admin_email'       => LabSetting::get('admin_email', ''),
            'operating_start'   => LabSetting::get('operating_start', '08:00'),
            'operating_end'     => LabSetting::get('operating_end', '22:00'),
            'operating_days'    => $this->daysToArray(LabSetting::get('operating_days', '1,2,3,4,5,6')),
            'max_session_hours' => LabSetting::get('max_session_hours', '4'),
            'buffer_minutes'    => LabSetting::get('buffer_minutes', '15'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        // operating_days use ISO weekday numbers (1=Mon … 7=Sun) — matches how
        // CalendarController::validateBusinessRules() checks Carbon::dayOfWeekIso.
        $validated = $request->validate([
            'lab_name'          => ['required', 'string', 'max:255'],
            'admin_email'       => ['required', 'email', 'max:255'],
            'operating_start'   => ['required', 'date_format:H:i'],
            'operating_end'     => ['required', 'date_format:H:i', 'after:operating_start'],
            'operating_days'    => ['required', 'array', 'min:1'],
            'operating_days.*'  => ['integer', 'between:1,7'],
            'max_session_hours' => ['required', 'integer', 'min:1', 'max:8'],
            'buffer_minutes'    => ['required', 'integer', 'min:0', 'max:60'],
        ], [
            'operating_end.after'     => 'Jam tutup harus setelah jam buka.',
            'operating_days.required' => 'Pilih minimal satu hari operasional.',
            'admin_email.email'       => 'Format email admin tidak valid.',
        ]);

        // Snapshot before for the audit diff.
        $old = $this->snapshot();

        $days = collect($validated['operating_days'])
            ->map(fn ($d) => (int) $d)->unique()->sort()->values()->implode(',');

        LabSetting::set('lab_name',          $validated['lab_name']);
        LabSetting::set('admin_email',       $validated['admin_email']);
        LabSetting::set('operating_start',   $validated['operating_start']);
        LabSetting::set('operating_end',     $validated['operating_end']);
        LabSetting::set('operating_days',    $days);
        LabSetting::set('max_session_hours', (string) $validated['max_session_hours']);
        LabSetting::set('buffer_minutes',    (string) $validated['buffer_minutes']);

        $new = $this->snapshot();

        // Log only the keys that actually changed.
        $changedOld = array_filter($old, fn ($v, $k) => ($new[$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH);
        $changedNew = array_intersect_key($new, $changedOld);

        if (! empty($changedOld)) {
            AuditLogService::record('settings.updated', null, $changedOld, $changedNew);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengaturan lab berhasil disimpan.');
    }

    private function snapshot(): array
    {
        $out = [];
        foreach (self::KEYS as $k) {
            $out[$k] = (string) LabSetting::get($k, '');
        }
        return $out;
    }

    private function daysToArray(mixed $raw): array
    {
        return array_values(array_map(
            'intval',
            array_filter(explode(',', (string) $raw), fn ($v) => $v !== '')
        ));
    }
}
