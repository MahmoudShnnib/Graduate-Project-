<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Http\Resources\Api\V1\SurveyResource;
use App\Models\Course;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $student = $request->user()->student;
        $semesterId = $request->integer('semester_id') ?: Semester::query()->where('is_active', true)->value('id');

        return SurveyResource::collection(
            $student->courseSurveys()
                ->where('semester_id', $semesterId)
                ->with('course')
                ->orderBy('priority')
                ->get()
        );
    }

    public function availableCourses(Request $request): AnonymousResourceCollection
    {
        $student = $request->user()->student;
        $passedIds = $student->getCompletedCourseIds();

        $courses = Course::query()
            ->whereHas('specializations', fn ($q) => $q->where('specialization_id', $student->specialization_id))
            ->whereNotIn('id', $passedIds)
            ->where('is_active', true)
            ->with(['specializations' => fn ($q) => $q->where('specialization_id', $student->specialization_id)])
            ->get();

        return CourseResource::collection($courses);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        if (! $student->canPerformActions()) {
            return response()->json(['message' => 'لا يمكن تقديم الاستطلاع — الحساب غير نشط.'], 422);
        }

        $semester = Semester::query()->where('is_active', true)->firstOrFail();
        if (! $semester->survey_open) {
            return response()->json(['message' => 'نافذة الاستطلاع مغلقة.'], 422);
        }

        $data = $request->validate([
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        DB::transaction(function () use ($student, $semester, $data) {
            $student->courseSurveys()->where('semester_id', $semester->id)->delete();
            foreach ($data['course_ids'] as $i => $courseId) {
                CourseSurvey::create([
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'course_id' => $courseId,
                    'priority' => $i + 1,
                ]);
            }
        });

        return response()->json(['message' => 'تم حفظ استطلاعك']);
    }

    public function destroy(Request $request, CourseSurvey $survey): JsonResponse
    {
        if ($survey->student_id !== $request->user()->student->id) {
            abort(403);
        }
        $survey->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
