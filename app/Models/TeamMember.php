<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = ['team_id', 'student_name', 'student_id_number'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
