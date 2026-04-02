<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{

    public function run(): void
    {
        \App\Models\ApplicationSetting::create([
            'current_year' => '2026',
            'active_semester' => 'autumn'
        ]);    }
}
