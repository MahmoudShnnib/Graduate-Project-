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
            'enrollments.student.user',
        ]);

        $students = $courseOffering->enrollments
            ->sortBy(fn ($e) => $e->student->user->name)
            ->values();

        return view('prints.course-students', [
            'courseOffering' => $courseOffering,
            'students' => $students,
        ]);
    }
}
