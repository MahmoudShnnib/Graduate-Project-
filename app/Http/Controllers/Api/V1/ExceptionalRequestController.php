<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExceptionalRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseResource;
use App\Http\Resources\Api\V1\ExceptionalRequestResource;
use App\Models\Course;
use App\Models\ExceptionalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExceptionalRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ExceptionalRequestResource::collection(
            $request->user()->student->exceptionalRequests()
                ->with('course')
                ->latest()
                ->get()
        );
    }

    public function options(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $failedCourseIds = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at')->where('letter', 'F'))
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id')
            ->unique()
            ->values()
            ->all();

        $failedCourses = Course::query()->whereIn('id', $failedCourseIds)->get();

        return response()->json([
            'failed_courses' => CourseResource::collection($failedCourses),
            'opportunity_types' => [
                ['value' => 'regular_failure', 'label' => 'فرصة رسوب عادي'],
                ['value' => 'attendance', 'label' => 'فرصة حضور'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        if (! $student->canPerformActions()) {
            return response()->json(['message' => 'الحساب غير نشط.'], 422);
        }

        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'opportunity_type' => ['required', 'in:regular_failure,attendance'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $req = ExceptionalRequest::create($data + [
            'student_id' => $student->id,
            'status' => ExceptionalRequestStatus::Pending,
            'can_reapply' => true,
        ]);

        return response()->json([
            'message' => 'تم تقديم الطلب',
            'request' => new ExceptionalRequestResource($req->load('course')),
        ], 201);
    }
}
