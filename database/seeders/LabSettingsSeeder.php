<?php

namespace Database\Seeders;

use App\Models\LabSetting;
use Illuminate\Database\Seeder;

class LabSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'lab_name',          'value' => 'Laboratorium Komputer UKRIDA',   'description' => 'Nama laboratorium'],
            ['key' => 'admin_email',        'value' => 'admin@ukrida.ac.id',             'description' => 'Email admin penerima notifikasi'],
            ['key' => 'buffer_minutes',     'value' => '15',                             'description' => 'Waktu buffer antar sesi (menit)'],
            ['key' => 'operating_start',    'value' => '08:00',                          'description' => 'Jam buka laboratorium'],
            ['key' => 'operating_end',      'value' => '22:00',                          'description' => 'Jam tutup laboratorium'],
            ['key' => 'operating_days',     'value' => '1,2,3,4,5,6',                   'description' => 'Hari operasional (1=Senin, 7=Minggu)'],
            ['key' => 'max_session_hours',  'value' => '4',                              'description' => 'Maksimum durasi peminjaman (jam)'],
            ['key' => 'session_lifetime',   'value' => '120',                            'description' => 'Batas waktu sesi login (menit)'],
        ];

        foreach ($settings as $setting) {
            LabSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
