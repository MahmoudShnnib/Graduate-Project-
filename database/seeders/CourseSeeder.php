<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // 4 general courses (all specializations)
        $generalCourses = [
            ['name' => 'اللغة العربية', 'code' => 'GEN-101', 'credits' => 3, 'type' => 'general'],
            ['name' => 'اللغة الإنجليزية', 'code' => 'GEN-102', 'credits' => 3, 'type' => 'general'],
            ['name' => 'مناهج البحث العلمي', 'code' => 'GEN-103', 'credits' => 3, 'type' => 'general'],
            ['name' => 'الإحصاء التطبيقي', 'code' => 'GEN-104', 'credits' => 3, 'type' => 'general'],
        ];

        foreach ($generalCourses as $course) {
            Course::create($course);
        }

        // Specialized + elective courses per specialization
        $specializations = Specialization::all();

        $specCourses = [
            2 => [ // Computer Science
                ['name' => 'البرمجة المتقدمة', 'code' => 'CS-201', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'هياكل البيانات', 'code' => 'CS-202', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'قواعد البيانات المتقدمة', 'code' => 'CS-203', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'الذكاء الاصطناعي', 'code' => 'CS-204', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'تعلم الآلة', 'code' => 'CS-301', 'credits' => 3, 'type' => 'elective'],
                ['name' => 'الحوسبة السحابية', 'code' => 'CS-302', 'credits' => 3, 'type' => 'elective'],
            ],
            3 => [ // Information Systems
                ['name' => 'تحليل النظم', 'code' => 'IS-201', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'إدارة قواعد البيانات', 'code' => 'IS-202', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'أمن المعلومات', 'code' => 'IS-203', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'نظم دعم القرار', 'code' => 'IS-204', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'التجارة الإلكترونية', 'code' => 'IS-301', 'credits' => 3, 'type' => 'elective'],
                ['name' => 'إدارة المشاريع', 'code' => 'IS-302', 'credits' => 3, 'type' => 'elective'],
            ],
            4 => [ // Networks
                ['name' => 'شبكات الحاسوب المتقدمة', 'code' => 'NET-201', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'أمن الشبكات', 'code' => 'NET-202', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'الاتصالات اللاسلكية', 'code' => 'NET-203', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'بروتوكولات الشبكات', 'code' => 'NET-204', 'credits' => 3, 'type' => 'specialized'],
                ['name' => 'إنترنت الأشياء', 'code' => 'NET-301', 'credits' => 3, 'type' => 'elective'],
                ['name' => 'الحوسبة الموزعة', 'code' => 'NET-302', 'credits' => 3, 'type' => 'elective'],
            ],
        ];

        foreach ($specializations as $spec) {
            if (isset($specCourses[$spec->code])) {
                foreach ($specCourses[$spec->code] as $course) {
                    Course::create(array_merge($course, ['specialization_id' => $spec->id]));
                }
            }
        }
    }
}
