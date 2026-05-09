<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    protected $fillable = ['unit_number', 'label', 'status', 'specs_note'];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_computers');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online';
    }
}
