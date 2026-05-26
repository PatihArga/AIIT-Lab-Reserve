<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'Teknik Informatika', 'email' => 'ti.ukrida@gmail.com'],
            ['name' => 'Sistem Informasi',   'email' => 'si.ukrida@gmail.com'],
            ['name' => 'Teknik Elektro',     'email' => 'te.ukrida@gmail.com'],
            ['name' => 'Teknik Industri',    'email' => 'tk.ukrida@gmail.com'],
        ];

        foreach ($programs as $program) {
            StudyProgram::updateOrCreate(
                ['name' => $program['name']],
                array_merge($program, ['is_active' => true])
            );
        }
    }
}
