<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseTreeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $passedIds = $student->getCompletedCourseIds();

        $failedIds = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at')->where('letter', 'F'))
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id')
            ->unique()
            ->values()
            ->all();

        $enrolledIds = $student->activeEnrollments()
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id')
            ->unique()
            ->values()
            ->all();

        $courses = Course::query()
            ->whereHas('specializations', fn ($q) => $q->where('specialization_id', $student->specialization_id))
            ->with(['specializations' => fn ($q) => $q->where('specialization_id', $student->specialization_id)])
            ->where('is_active', true)
            ->get()
            ->map(function (Course $course) use ($passedIds, $failedIds, $enrolledIds) {
                $type = $course->specializations->first()?->pivot->type ?? 'general';
                $courseStatus = match (true) {
                    in_array($course->id, $passedIds, true) => 'passed',
                    in_array($course->id, $enrolledIds, true) => 'enrolled',
                    in_array($course->id, $failedIds, true) => 'failed',
                    default => 'not_taken',
                };

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                    'credits' => $course->credits,
                    'type' => $type,
                    'status' => $courseStatus,
                ];
            });

        $grouped = [
            'general' => $courses->where('type', 'general')->values(),
            'specialized' => $courses->where('type', 'specialized')->values(),
            'elective' => $courses->where('type', 'elective')->values(),
        ];

        return response()->json(['data' => $grouped]);
    }
}
