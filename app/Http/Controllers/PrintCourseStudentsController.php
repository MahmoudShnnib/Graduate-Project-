<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use Illuminate\View\View;

class PrintCourseStudentsController extends Controller
{
    public function __invoke(CourseOffering $courseOffering): View
    {
        $courseOffering->load([
            'course',
            'semester',
            'activeEnrollments.student.user',
            'activeEnrollments.student.specialization',
        ]);

        $students = $courseOffering->activeEnrollments
            ->sortBy([
                ['student.specialization.name', 'asc'],
                ['student.user.name', 'asc'],
            ])
            ->values();

        return view('prints.course-students', [
            'courseOffering' => $courseOffering,
            'students' => $students,
        ]);
    }
}
