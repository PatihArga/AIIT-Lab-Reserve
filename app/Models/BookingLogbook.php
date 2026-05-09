<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLogbook extends Model
{
    protected $fillable = [
        'booking_id', 'category', 'checkpoint_progress',
        'related_course', 'supervisor_name', 'duration_sufficient',
        'special_software', 'needs_internet', 'needs_installation',
        'external_devices', 'priority_level', 'priority_reason', 'session_target',
    ];

    protected function casts(): array
    {
        return [
            'duration_sufficient'  => 'boolean',
            'needs_internet'       => 'boolean',
            'needs_installation'   => 'boolean',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
