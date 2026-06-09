<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Write a single audit entry.
     *
     * @param  string      $action     semantic action name, e.g. 'booking.approved'
     * @param  Model|null  $auditable  the related model (null for model-less actions like 'settings.updated')
     * @param  array       $old        previous values (empty → stored as null)
     * @param  array       $new        new values (empty → stored as null)
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        array $old = [],
        array $new = [],
    ): void {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id'   => $auditable?->getKey(),
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
