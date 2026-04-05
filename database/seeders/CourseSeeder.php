<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $commsId = Specialization::where('code', 1)->firstOrFail()->id;   // الإتصالات
        $csId = Specialization::where('code', 2)->firstOrFail()->id;   // الحاسب الآلي
        $ctrlId = Specialization::where('code', 3)->firstOrFail()->id;   // التحكم الآلي

        // ----------------------------------------------------------------
        // 'specializations' => [ specialization_id => pivot_type ]
        // ----------------------------------------------------------------
        $courses = [

            // ── Common (عام) — shared by all three specializations ──────
            ['name' => 'Advanced Mathematics',      'code' => 'EE601', 'specializations' => [$commsId => 'general', $csId => 'general', $ctrlId => 'general']],
            ['name' => 'Digital Signal Processing', 'code' => 'EE602', 'specializations' => [$commsId => 'general', $csId => 'general', $ctrlId => 'general']],
            ['name' => 'Advanced Electronics',      'code' => 'EE603', 'specializations' => [$commsId => 'general', $csId => 'general', $ctrlId => 'general']],
            ['name' => 'Computing',                 'code' => 'EE604', 'specializations' => [$commsId => 'general', $csId => 'general', $ctrlId => 'general']],

            // CM614 is an elective in all three specializations
            ['name' => 'Information Theory', 'code' => 'CM614', 'specializations' => [$commsId => 'elective', $csId => 'elective', $ctrlId => 'elective']],

            // ── الإتصالات — core ────────────────────────────────────────
            ['name' => 'Communication Networks',          'code' => 'CM611', 'specializations' => [$commsId => 'specialized']],
            ['name' => 'Microwave Engineering',           'code' => 'MW612', 'specializations' => [$commsId => 'specialized']],
            ['name' => 'Wireless Communication Networks', 'code' => 'CM613', 'specializations' => [$commsId => 'specialized']],
            ['name' => 'Antenna Engineering',             'code' => 'MW613', 'specializations' => [$commsId => 'specialized']],

            // ── الإتصالات — elective ────────────────────────────────────
            ['name' => 'Optical Communication', 'code' => 'CM624', 'specializations' => [$commsId => 'elective']],
            ['name' => 'Radio Communication',   'code' => 'CM222', 'specializations' => [$commsId => 'elective']],
            ['name' => 'Radio Communication',   'code' => 'CM625', 'specializations' => [$commsId => 'elective']],

            // ── الحاسب الآلي — core ─────────────────────────────────────
            ['name' => 'Microprocessors & Embedded Systems', 'code' => 'CP611', 'specializations' => [$csId => 'specialized']],
            ['name' => 'VHDL Design and Synthesis',          'code' => 'CP612', 'specializations' => [$csId => 'specialized']],
            // CP614: core in الحاسب الآلي, elective in التحكم الآلي
            ['name' => 'Fault Tolerant Systems',             'code' => 'CP614', 'specializations' => [$csId => 'specialized', $ctrlId => 'elective']],
            ['name' => 'Parallel Architectures',             'code' => 'CP619', 'specializations' => [$csId => 'specialized']],

            // ── الحاسب الآلي — elective ─────────────────────────────────
            ['name' => 'Network Applications & Administration', 'code' => 'CP616', 'specializations' => [$csId => 'elective']],
            ['name' => 'Data Acquisition Systems',              'code' => 'CP617', 'specializations' => [$csId => 'elective']],
            ['name' => 'Computer Security',                     'code' => 'CP615', 'specializations' => [$csId => 'elective']],

            // ── التحكم الآلي — core ─────────────────────────────────────
            // CT614: core in التحكم الآلي, elective in الحاسب الآلي
            ['name' => 'Sensors and Actuators',         'code' => 'CT614', 'specializations' => [$ctrlId => 'specialized', $csId => 'elective']],
            ['name' => 'Multivariable Control Systems', 'code' => 'CT613', 'specializations' => [$ctrlId => 'specialized']],
            ['name' => 'Modeling and Simulation',       'code' => 'CT612', 'specializations' => [$ctrlId => 'specialized']],
            ['name' => 'Advanced Control Systems',      'code' => 'CT611', 'specializations' => [$ctrlId => 'specialized']],

            // ── التحكم الآلي — elective ─────────────────────────────────
            ['name' => 'Embedded Systems', 'code' => 'CT622', 'specializations' => [$ctrlId => 'elective']],
        ];

        foreach ($courses as $data) {
            $course = Course::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'credits' => 3,
                'is_active' => true,
            ]);

            $pivotData = [];
            foreach ($data['specializations'] as $specId => $type) {
                $pivotData[$specId] = ['type' => $type];
            }

            $course->specializations()->attach($pivotData);
        }
    }
}
