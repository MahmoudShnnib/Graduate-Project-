<?php

namespace App\Actions;

use App\Models\Specialization;
use App\Models\Student;
use Illuminate\Auth\Access\Gate;

class GenerateStudentNumberAction
{
    public function __invoke(
        Specialization $specialization,
        int $semesterType
    ): string
    {
        $year = now()->format('y');

        //add somet cahnges

        $lastStudent = Student::whereBelongsTo($specialization)
            ->latest()
            ->first();


        //TODO USE STR FACADE
        $sequence = $lastStudent ? ((int) substr($lastStudent->student_number, -2) + 1) : 1;

        //STR facade
        $sequence = str_pad($sequence, 2, '0', STR_PAD_LEFT);

        return $year . $specialization->id . $semesterType . $sequence;
    }
}
