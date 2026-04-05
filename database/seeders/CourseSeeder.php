<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------
        // Common courses — shared by all specializations
        // -------------------------------------------------------
        $commonCourses = [
            ['name' => 'Advanced Mathematics', 'code' => 'EE601', 'credits' => 3, 'type' => 'general'],
            ['name' => 'Digital Signal Processing', 'code' => 'EE602', 'credits' => 3, 'type' => 'general'],
            ['name' => 'Advanced Electronics', 'code' => 'EE603', 'credits' => 3, 'type' => 'general'],
            ['name' => 'Computing', 'code' => 'EE604', 'credits' => 3, 'type' => 'general'],
            // CM614 is an elective in all three specializations; stored without a
            // specific specialization so it appears as a shared elective option.
            ['name' => 'Information Theory', 'code' => 'CM614', 'credits' => 3, 'type' => 'elective'],
        ];

        foreach ($commonCourses as $course) {
            Course::create($course);
        }

        // -------------------------------------------------------
        // Per-specialization courses
        // -------------------------------------------------------
        $comms = Specialization::where('code', 1)->firstOrFail();   // الإتصالات
        $cs    = Specialization::where('code', 2)->firstOrFail();   // الحاسب الآلي
        $ctrl  = Specialization::where('code', 3)->firstOrFail();   // التحكم الآلي

        $specCourses = [

            // ---- الإتصالات (P=1) --------------------------------
            $comms->id => [
                // Core
                ['name' => 'Communication Networks',          'code' => 'CM611', 'type' => 'specialized'],
                ['name' => 'Microwave Engineering',           'code' => 'MW612', 'type' => 'specialized'],
                ['name' => 'Wireless Communication Networks', 'code' => 'CM613', 'type' => 'specialized'],
                ['name' => 'Antenna Engineering',             'code' => 'MW613', 'type' => 'specialized'],
                // Elective (select two)
                ['name' => 'Optical Communication',          'code' => 'CM624', 'type' => 'elective'],
                ['name' => 'Radio Communication',            'code' => 'CM222', 'type' => 'elective'],
                ['name' => 'Radio Communication',            'code' => 'CM625', 'type' => 'elective'],
                // CM614 Information Theory is already seeded as a shared elective above
            ],

            // ---- الحاسب الآلي (P=2) -----------------------------
            $cs->id => [
                // Core
                ['name' => 'Microprocessors & Embedded Systems', 'code' => 'CP611', 'type' => 'specialized'],
                ['name' => 'VHDL Design and Synthesis',          'code' => 'CP612', 'type' => 'specialized'],
                // CP614 is core here; CT614 is elective in الحاسب but core in التحكم —
                // both are assigned to their "primary" specialization (see notes below).
                ['name' => 'Fault Tolerant Systems',             'code' => 'CP614', 'type' => 'specialized'],
                ['name' => 'Parallel Architectures',             'code' => 'CP619', 'type' => 'specialized'],
                // Elective (select two)
                ['name' => 'Network Applications & Administration', 'code' => 'CP616', 'type' => 'elective'],
                ['name' => 'Data Acquisition Systems',              'code' => 'CP617', 'type' => 'elective'],
                ['name' => 'Computer Security',                     'code' => 'CP615', 'type' => 'elective'],
                // CM614 Information Theory → shared elective (seeded above)
                // CT614 Sensors and Actuators  → seeded under التحكم الآلي as specialized
            ],

            // ---- التحكم الآلي (P=3) -----------------------------
            $ctrl->id => [
                // Core
                // CT614 is core here; it is also listed as elective in الحاسب الآلي,
                // but since the code must be unique it is stored under التحكم الآلي.
                ['name' => 'Sensors and Actuators',           'code' => 'CT614', 'type' => 'specialized'],
                ['name' => 'Multivariable Control Systems',   'code' => 'CT613', 'type' => 'specialized'],
                ['name' => 'Modeling and Simulation',         'code' => 'CT612', 'type' => 'specialized'],
                ['name' => 'Advanced Control Systems',        'code' => 'CT611', 'type' => 'specialized'],
                // Elective (select two)
                ['name' => 'Embedded Systems',                'code' => 'CT622', 'type' => 'elective'],
                // CP614 Fault Tolerant Systems → seeded under الحاسب الآلي as specialized
                // CM614 Information Theory     → shared elective (seeded above)
            ],
        ];

        foreach ($specCourses as $specializationId => $courses) {
            foreach ($courses as $course) {
                Course::create(array_merge(
                    ['credits' => 3, 'specialization_id' => $specializationId],
                    $course
                ));
            }
        }
    }
}
