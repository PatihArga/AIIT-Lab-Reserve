<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'study_program_id', 'name', 'email', 'password',
        'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_active'      => 'boolean',
            'last_login_at'  => 'datetime',
        ];
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function teamAccount()
    {
        return $this->hasOne(Team::class, 'user_id');
    }

    public function picTeams()
    {
        return $this->hasMany(Team::class, 'pic_lecturer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLecturer(): bool
    {
        return $this->role === 'lecturer';
    }

    public function isTeam(): bool
    {
        return $this->role === 'team';
    }
}
