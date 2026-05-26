<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin accounts are not tied to any study program.
        User::updateOrCreate(
            ['email' => 'admin@ukrida.ac.id'],
            [
                'study_program_id' => null,
                'name'      => 'Administrator',
                'password'  => Hash::make('Admin@123'),
                'gmail'     => 'admin.ukrida@gmail.com',
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
