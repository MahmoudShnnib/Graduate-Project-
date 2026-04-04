<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        Semester::create([
            'name' => 'خريف 2025',
            'year' => 2025,
            'season' => 'autumn',
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-15',
            'add_drop_start' => '2025-09-01',
            'add_drop_end' => '2025-09-15',
            'is_active' => true,
        ]);
    }
}
