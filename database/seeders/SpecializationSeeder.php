<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            ['name' => 'الإتصالات', 'code' => 1],
            ['name' => 'الحاسب الآلي', 'code' => 2],
            ['name' => 'التحكم الآلي', 'code' => 3],
        ];

        foreach ($specializations as $specialization) {
            Specialization::create($specialization);
        }
    }
}
