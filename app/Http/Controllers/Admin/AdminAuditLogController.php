<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Computer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    /** action → [human description, dot colour class] */
    private const PRESENTATION = [
        'booking.submitted'       => ['Permintaan baru dikirim',         'bg-mark-500'],
        'booking.approved'        => ['Reservasi disetujui',             'bg-status-approved'],
        'booking.rejected'        => ['Reservasi ditolak',               'bg-status-rejected'],
        'booking.auto_rejected'   => ['Ditolak otomatis (bentrok slot)', 'bg-status-rejected'],
        'booking.completed'       => ['Reservasi diselesaikan',          'bg-[#2eb8a0]'],
        'booking.cancelled'       => ['Reservasi dibatalkan',            'bg-ink-700/30'],
        'logbook.updated'         => ['Logbook diperbarui',              'bg-mark-500'],
        'computer.status_changed' => ['Status unit komputer diubah',     'bg-ink-700/20'],
        'user.created'            => ['Akun dosen dibuat',               'bg-status-review'],
        'user.updated'            => ['Akun dosen diperbarui',           'bg-status-review'],
        'team.created'            => ['Tim baru dibuat',                 'bg-status-review'],
        'team.updated'            => ['Tim diperbarui',                  'bg-status-review'],
        'settings.updated'        => ['Pengaturan lab diperbarui',       'bg-ink-700/20'],
    ];

    /** field key → human label, used when rendering the before/after diff */
    private const FIELD_LABELS = [
        'checkpoint_progress' => 'Catatan logbook',
        'needs_installation'  => 'Instalasi perangkat lunak',
        'special_software'    => 'Perangkat lunak',
        'status'              => 'Status',
    ];

    public function index(Request $request): View
    {
        $query = AuditLog::with(['user', 'auditable'])->latest('created_at')->latest('id');

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($w) use ($term) {
                $w->where('action', 'like', "%{$term}%")
                  ->orWhereHasMorph('auditable', [Booking::class], fn ($b) => $b->where('booking_code', 'like', "%{$term}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString()->through(fn ($log) => $this->present($log));

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');
        $users   = User::whereIn('id', AuditLog::whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.audit-log.index', compact('logs', 'actions', 'users'));
    }

    /** Flatten one log row into the shape the view renders. */
    private function present(AuditLog $log): array
    {
        [$desc, $color] = self::PRESENTATION[$log->action] ?? [$log->action, 'bg-ink-700/20'];

        $target = match (true) {
            $log->auditable instanceof Booking  => $log->auditable->booking_code,
            $log->auditable instanceof Computer => $log->auditable->label,
            $log->auditable instanceof User     => $log->auditable->name,
            $log->auditable instanceof Team     => $log->auditable->name,
            $log->action === 'settings.updated' => 'lab_settings',
            default                             => '—',
        };

        return [
            'time'    => optional($log->created_at)->translatedFormat('d M Y · H:i') ?? '—',
            'user'    => $log->user?->name ?? 'Sistem',
            'action'  => $log->action,
            'desc'    => $desc,
            'color'   => $color,
            'target'  => $target,
            'changes' => $this->changes($log),
        ];
    }

    /**
     * Build a per-field before/after diff for the view. Returns [] when the
     * entry carries no recorded values.
     */
    private function changes(AuditLog $log): array
    {
        // Only logbook edits surface a full before/after diff; other actions
        // keep the compact one-line presentation.
        if ($log->action !== 'logbook.updated') {
            return [];
        }

        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $rows = [];
        foreach (array_keys($old + $new) as $key) {
            $rows[] = [
                'label'  => self::FIELD_LABELS[$key] ?? $key,
                'before' => $this->displayValue($key, $old[$key] ?? null),
                'after'  => $this->displayValue($key, $new[$key] ?? null),
            ];
        }

        return $rows;
    }

    /** Render a stored value for display (booleans → Ya/Tidak, empty → em dash). */
    private function displayValue(string $key, $value): string
    {
        if ($key === 'needs_installation' || is_bool($value)) {
            if ($value === null) {
                return '—';
            }
            return $value ? 'Ya' : 'Tidak';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }
}
