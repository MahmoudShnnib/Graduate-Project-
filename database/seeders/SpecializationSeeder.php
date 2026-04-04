<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        Specialization::insert([
            ['name' => 'علوم الحاسوب', 'code' => 2],
            ['name' => 'نظم المعلومات', 'code' => 3],
            ['name' => 'الشبكات', 'code' => 4],
        ]);
    }
}
