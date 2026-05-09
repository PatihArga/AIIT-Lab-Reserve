<?php

namespace Database\Seeders;

use App\Models\Computer;
use Illuminate\Database\Seeder;

class ComputerSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            Computer::updateOrCreate(
                ['unit_number' => $i],
                [
                    'label'  => 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status' => 'online',
                ]
            );
        }
    }
}
