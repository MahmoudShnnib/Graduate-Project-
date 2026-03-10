<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        Specialization::insert([
            [
                'name' => 'الاتصالات',
                'code' => 1,
            ],
            [
                'name' => 'الحاسب الآلي',
                'code' => 2,
            ],
            [
                'name' => 'التحكم',
                'code' => 3,
            ],
        ]);
    }
}
