<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_code', 'user_id', 'booking_type', 'date',
        'start_time', 'end_time', 'status', 'admin_notes',
        'google_event_id', 'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function computers()
    {
        return $this->belongsToMany(Computer::class, 'booking_computers');
    }

    public function logbook()
    {
        return $this->hasOne(BookingLogbook::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['approved', 'under_review']);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['submitted', 'under_review', 'approved']);
    }
}
