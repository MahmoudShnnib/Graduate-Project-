<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EnrollmentResource;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $student = $request->user()->student;

        $semesterId = $request->integer('semester_id') ?: Semester::query()->where('is_active', true)->value('id');

        $enrollments = $student->enrollments()
            ->with(['courseOffering.course', 'courseOffering.faculty', 'grade'])
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semesterId))
            ->latest()
            ->get();

        return EnrollmentResource::collection($enrollments);
    }
}
