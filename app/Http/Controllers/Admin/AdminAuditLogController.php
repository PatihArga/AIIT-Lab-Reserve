<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Computer;
use App\Models\Team;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    /** action → [human description, accent hex] (drives the timeline dot + tag) */
    private const PRESENTATION = [
        'booking.submitted'       => ['Permintaan baru dikirim',         '#D9A300'],
        'booking.approved'        => ['Reservasi disetujui',             '#16A34A'],
        'booking.rejected'        => ['Reservasi ditolak',               '#DC2626'],
        'booking.auto_rejected'   => ['Ditolak otomatis (bentrok slot)', '#DC2626'],
        'booking.completed'       => ['Reservasi diselesaikan',          '#0891B2'],
        'booking.cancelled'       => ['Reservasi dibatalkan',            '#6B7280'],
        'logbook.updated'         => ['Logbook diperbarui',              '#C99400'],
        'computer.status_changed' => ['Status unit komputer diubah',     '#4A5568'],
        'user.created'            => ['Akun dosen dibuat',               '#7C3AED'],
        'user.updated'            => ['Akun dosen diperbarui',           '#7C3AED'],
        'team.created'            => ['Tim baru dibuat',                 '#7C3AED'],
        'team.updated'            => ['Tim diperbarui',                  '#7C3AED'],
        'settings.updated'        => ['Pengaturan lab diperbarui',       '#7C5CCF'],
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
        $query = $this->buildQuery($request);

        $stats = $this->statsFor($query);

        $logs = $query->paginate(20)->withQueryString()->through(fn ($log) => $this->present($log));

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');
        $users   = User::whereIn('id', AuditLog::whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.audit-log.index', compact('logs', 'actions', 'users', 'stats'));
    }

    /**
     * Server-side PDF export of the audit log honouring the active filters.
     * Pagination is dropped (capped at 500 rows) so the document is a single
     * continuous, filtered timeline.
     */
    public function exportPdf(Request $request): Response
    {
        $query = $this->buildQuery($request);

        $stats = $this->statsFor($query);

        $logs = $query->limit(500)->get()->map(fn ($log) => $this->present($log));

        $pdf = Pdf::loadView('admin.audit-log.pdf', [
            'logs'          => $logs,
            'stats'         => $stats,
            'filterSummary' => $this->filterSummary($request),
        ])->setPaper('a4', 'portrait');

        $filename = 'Audit-log AIIT - ' . now()->locale('id')->translatedFormat('d F Y') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Build the filtered audit-log query shared by the screen and the PDF
     * export. Eager-loads + ordering applied; pagination is NOT.
     */
    private function buildQuery(Request $request): Builder
    {
        $query = AuditLog::with(['user', 'auditable'])->latest('created_at')->latest('id');

        // Action filter is a checkbox group (`actions[]`). A hidden `af` marker
        // signals the group was submitted: with the marker present, only the
        // checked actions show (empty = show none); without it (fresh load),
        // everything shows — i.e. all boxes default to checked.
        if ($request->has('af')) {
            $query->whereIn('action', (array) $request->input('actions', []));
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

        return $query;
    }

    /**
     * Stats block: total/today mirror the active filter; processed/active_users
     * stay system-wide.
     *
     * @return array<string, int>
     */
    private function statsFor(Builder $query): array
    {
        return [
            // These two reflect the current filter selection.
            'total'        => (clone $query)->count(),
            'today'        => (clone $query)->whereDate('created_at', today())->count(),
            // These remain system-wide.
            'processed'    => AuditLog::whereIn('action', [
                'booking.approved', 'booking.rejected', 'booking.auto_rejected', 'booking.completed',
            ])->count(),
            'active_users' => AuditLog::whereNotNull('user_id')->distinct()->count('user_id'),
        ];
    }

    /** Short human description of the active filters, for the PDF header. */
    private function filterSummary(Request $request): string
    {
        $parts = [];

        if ($request->has('af')) {
            $count = count((array) $request->input('actions', []));
            $parts[] = $count > 0 ? "{$count} jenis aksi" : 'tanpa aksi';
        }
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $parts[] = 'oleh ' . (User::find($request->user_id)?->name ?? 'pengguna');
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->translatedFormat('d M Y') : '…';
            $to   = $request->filled('date_to') ? Carbon::parse($request->date_to)->translatedFormat('d M Y') : '…';
            $parts[] = "{$from} – {$to}";
        }
        if ($request->filled('q')) {
            $parts[] = 'pencarian "' . $request->q . '"';
        }

        return $parts === [] ? 'Semua aktivitas' : implode(' · ', $parts);
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

        $created = $log->created_at;

        return [
            'date_key'    => $created ? $created->toDateString() : '—',
            'day_label'   => $created ? $created->locale('id')->translatedFormat('d F Y') : '—',
            'day_rel'     => $this->relativeDay($created),
            'time'        => $created ? $created->format('H:i') : '—',
            'user'        => $log->user?->name ?? 'Sistem',
            'action'      => $log->action,
            'desc'        => $desc,
            'color'       => $color,
            'target'      => $target,
            'changes'     => $this->changes($log),
            'inline_diff' => $this->inlineDiff($log),
        ];
    }

    /** Indonesian relative-day label for the timeline group headers. */
    private function relativeDay(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }
        if ($date->isToday()) {
            return 'Hari ini';
        }
        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        return (int) $date->copy()->startOfDay()->diffInDays(today()) . ' hari lalu';
    }

    /**
     * Compact inline before/after for actions that change a single field
     * (currently computer.status_changed → status). Null when not applicable.
     */
    private function inlineDiff(AuditLog $log): ?array
    {
        if ($log->action !== 'computer.status_changed') {
            return null;
        }

        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        if (! isset($old['status']) && ! isset($new['status'])) {
            return null;
        }

        return [
            'before' => $old['status'] ?? '—',
            'after'  => $new['status'] ?? '—',
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
