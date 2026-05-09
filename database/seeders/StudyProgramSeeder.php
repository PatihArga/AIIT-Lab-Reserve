<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'Administrator',           'email_domain' => '@ukrida.ac.id'],
            ['name' => 'Teknik Informatika',     'email_domain' => '@ti.ukrida.ac.id'],
            ['name' => 'Sistem Informasi',        'email_domain' => '@si.ukrida.ac.id'],
            ['name' => 'Teknik Elektro',          'email_domain' => '@te.ukrida.ac.id'],
            ['name' => 'Teknik Industri',         'email_domain' => '@tk.ukrida.ac.id'],
        ];

        foreach ($programs as $program) {
            StudyProgram::updateOrCreate(
                ['email_domain' => $program['email_domain']],
                array_merge($program, ['is_active' => true])
            );
        }
    }
}
