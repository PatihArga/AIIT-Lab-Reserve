<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StudyProgramSeeder::class,
            AdminUserSeeder::class,
            ComputerSeeder::class,
            LabSettingsSeeder::class,
        ]);
    }
}
