<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestLecturerSeeder extends Seeder
{
    public function run(): void
    {
        $program = StudyProgram::where('name', 'Teknik Informatika')->first();

        User::updateOrCreate(
            ['email' => 'budi@ti.ukrida.ac.id'],
            [
                'study_program_id' => $program?->id,
                'name'      => 'Dr. Budi Santoso',
                'password'  => Hash::make('Test@123'),
                'role'      => 'lecturer',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tim.alpha@ti.ukrida.ac.id'],
            [
                'study_program_id' => $program?->id,
                'name'      => 'Tim Alpha',
                'password'  => Hash::make('Test@123'),
                'role'      => 'team',
                'is_active' => true,
            ]
        );
    }
}
