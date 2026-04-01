<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subject::insert([
            // مواد عامة
            [
                'name' => 'لغة عربية',
                'code' => 'GEN-001',
                'category' => 'general',
                'credits' => 3,
                'is_elective' => false,
            ],
            [
                'name' => 'لغة إنجليزية',
                'code' => 'GEN-002',
                'category' => 'general',
                'credits' => 3,
                'is_elective' => false,
            ],
            [
                'name' => 'رياضيات',
                'code' => 'GEN-003',
                'category' => 'general',
                'credits' => 3,
                'is_elective' => false,
            ],
            // مواد الحاسب
            [
                'name' => 'برمجة هيكلية',
                'code' => 'CS-001',
                'category' => 'cs',
                'credits' => 4,
                'is_elective' => false,
            ],
            [
                'name' => 'هياكل بيانات',
                'code' => 'CS-002',
                'category' => 'cs',
                'credits' => 4,
                'is_elective' => false,
            ],
            [
                'name' => 'قواعد بيانات',
                'code' => 'CS-003',
                'category' => 'cs',
                'credits' => 3,
                'is_elective' => false,
            ],
            // مواد الاتصالات
            [
                'name' => 'أسس اتصالات',
                'code' => 'COM-001',
                'category' => 'com',
                'credits' => 3,
                'is_elective' => false,
            ],
            [
                'name' => 'شبكات اتصالات',
                'code' => 'COM-002',
                'category' => 'com',
                'credits' => 4,
                'is_elective' => false,
            ],
            // مواد التحكم
            [
                'name' => 'نظم تحكم خطية',
                'code' => 'CTR-001',
                'category' => 'ctr',
                'credits' => 3,
                'is_elective' => false,
            ],
            [
                'name' => 'متحكمات منطقية',
                'code' => 'CTR-002',
                'category' => 'ctr',
                'credits' => 4,
                'is_elective' => false,
            ],
        ]);
    }
}
