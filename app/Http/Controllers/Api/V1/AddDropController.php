<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AddDropType;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AddDropRequestResource;
use App\Http\Resources\Api\V1\CourseOfferingResource;
use App\Http\Resources\Api\V1\EnrollmentResource;
use App\Models\AddDropRequest;
use App\Models\CourseOffering;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddDropController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $student = $request->user()->student;
        $semesterId = $request->integer('semester_id') ?: Semester::query()->where('is_active', true)->value('id');

        return AddDropRequestResource::collection(
            $student->addDropRequests()
                ->with('courseOffering.course')
                ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semesterId))
                ->latest()
                ->get()
        );
    }

    public function options(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        $semester = Semester::query()->where('is_active', true)->firstOrFail();

        $enrolledOfferingIds = $student->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
            ->pluck('course_offering_id');

        $passedIds = $student->getCompletedCourseIds();

        $addable = CourseOffering::query()
            ->where('semester_id', $semester->id)
            ->whereNotIn('id', $enrolledOfferingIds)
            ->whereHas('course.specializations', fn ($q) => $q->where('specialization_id', $student->specialization_id))
            ->whereDoesntHave('course', fn ($q) => $q->whereIn('id', $passedIds))
            ->with('course')
            ->get();

        $droppable = $student->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
            ->with('courseOffering.course')
            ->get();

        $usage = [
            'add' => (int) $student->addDropRequests()
                ->where('type', AddDropType::Add)
                ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
                ->count(),
            'drop' => (int) $student->addDropRequests()
                ->where('type', AddDropType::Drop)
                ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
                ->count(),
        ];

        return response()->json([
            'window_open' => $semester->isAddDropOpen(),
            'usage' => $usage + ['max' => 3],
            'addable' => CourseOfferingResource::collection($addable),
            'droppable' => EnrollmentResource::collection($droppable),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        $semester = Semester::query()->where('is_active', true)->firstOrFail();

        if (! $student->canPerformActions() || ! $semester->isAddDropOpen()) {
            return response()->json(['message' => 'لا يمكن تقديم الطلب الآن.'], 422);
        }

        $data = $request->validate([
            'course_offering_id' => ['required', 'integer', 'exists:course_offerings,id'],
            'type' => ['required', 'in:add,drop'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $usageCount = $student->addDropRequests()
            ->where('type', $data['type'])
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semester->id))
            ->count();

        if ($usageCount >= 3) {
            return response()->json(['message' => 'تم الوصول إلى الحد الأقصى للطلبات.'], 422);
        }

        $req = AddDropRequest::create([
            'student_id' => $student->id,
            'course_offering_id' => $data['course_offering_id'],
            'type' => $data['type'],
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'تم تقديم الطلب',
            'request' => new AddDropRequestResource($req->load('courseOffering.course')),
        ], 201);
    }
}
