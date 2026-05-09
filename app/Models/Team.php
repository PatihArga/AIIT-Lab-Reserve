<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'user_id', 'pic_lecturer_id', 'study_program_id',
        'name', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function userAccount()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function picLecturer()
    {
        return $this->belongsTo(User::class, 'pic_lecturer_id');
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }
}
