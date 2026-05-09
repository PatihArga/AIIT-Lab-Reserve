<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminProgram = StudyProgram::where('email_domain', '@ukrida.ac.id')->first();

        User::updateOrCreate(
            ['email' => 'admin@ukrida.ac.id'],
            [
                'study_program_id' => $adminProgram?->id,
                'name'      => 'Administrator',
                'password'  => Hash::make('Admin@123'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
