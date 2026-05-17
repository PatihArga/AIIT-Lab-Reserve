<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComputerStatusRequest;
use App\Models\AuditLog;
use App\Models\Computer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminComputerController extends Controller
{
    // M2: enforce valid status transitions. Same-state writes are allowed
    // because the "Edit Catatan" flow reuses this endpoint to update specs_note
    // while preserving the current status.
    private const ALLOWED_TRANSITIONS = [
        'online'      => ['online', 'maintenance', 'offline'],
        'maintenance' => ['maintenance', 'online'],
        'offline'     => ['offline', 'online'],
    ];

    public function index(): View
    {
        $computers = Computer::orderBy('unit_number')->get();
        $counts = [
            'online'      => $computers->where('status', 'online')->count(),
            'maintenance' => $computers->where('status', 'maintenance')->count(),
            'offline'     => $computers->where('status', 'offline')->count(),
        ];
        return view('admin.computers.index', compact('computers', 'counts'));
    }

    public function updateStatus(ComputerStatusRequest $request, Computer $computer): RedirectResponse
    {
        $oldStatus = $computer->status;
        $newStatus = $request->status;
        $oldSpecs  = $computer->specs_note;

        $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];
        if (! in_array($newStatus, $allowed, true)) {
            return back()->with('error', 'Transisi status tidak valid: ' . $oldStatus . ' → ' . $newStatus . '.');
        }

        $newSpecs = $request->filled('specs_note') ? $request->specs_note : $oldSpecs;

        $computer->update([
            'status'     => $newStatus,
            'specs_note' => $newSpecs,
        ]);

        $changedFields = [];
        if ($oldStatus !== $newStatus) {
            $changedFields['old']['status'] = $oldStatus;
            $changedFields['new']['status'] = $newStatus;
        }
        if ($oldSpecs !== $newSpecs) {
            $changedFields['old']['specs_note'] = $oldSpecs;
            $changedFields['new']['specs_note'] = $newSpecs;
        }

        if (! empty($changedFields)) {
            AuditLog::create([
                'user_id'        => auth()->id(),
                'action'         => 'computer.status_changed',
                'auditable_type' => Computer::class,
                'auditable_id'   => $computer->id,
                'old_values'     => $changedFields['old'] ?? [],
                'new_values'     => $changedFields['new'] ?? [],
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
            ]);
        }

        $msg = $oldStatus !== $newStatus
            ? $computer->label . ' diperbarui ke ' . $newStatus . '.'
            : 'Catatan ' . $computer->label . ' diperbarui.';

        return back()->with('success', $msg);
    }
}
