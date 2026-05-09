<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SuspensionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SuspensionRequestResource;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SuspensionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SuspensionRequestResource::collection(
            $request->user()->student->suspensionRequests()
                ->with('semester')
                ->latest()
                ->get()
        );
    }

    public function options(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $hasApproved = $student->suspensionRequests()->where('status', SuspensionStatus::Approved)->exists();
        $pending = $student->suspensionRequests()->where('status', SuspensionStatus::Pending)->with('semester')->first();

        if ($hasApproved) {
            return response()->json([
                'state' => 'approved_block',
                'pending' => null,
                'semesters' => [],
            ]);
        }

        if ($pending) {
            return response()->json([
                'state' => 'pending_block',
                'pending' => new SuspensionRequestResource($pending),
                'semesters' => [],
            ]);
        }

        $rejectedSemesterIds = $student->suspensionRequests()
            ->where('status', SuspensionStatus::Rejected)
            ->pluck('semester_id')
            ->all();

        $semesters = Semester::query()
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhere('start_date', '>', now());
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'disabled' => in_array($s->id, $rejectedSemesterIds, true),
                'reason' => in_array($s->id, $rejectedSemesterIds, true) ? 'تم رفض طلب سابق لهذا الفصل' : null,
            ]);

        return response()->json([
            'state' => 'available',
            'pending' => null,
            'semesters' => $semesters,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        if (! $student->canPerformActions()) {
            return response()->json(['message' => 'الحساب غير نشط.'], 422);
        }

        if ($student->suspensionRequests()->where('status', SuspensionStatus::Approved)->exists()) {
            return response()->json(['message' => 'لديك طلب موافق عليه مسبقاً.'], 422);
        }

        if ($student->suspensionRequests()->where('status', SuspensionStatus::Pending)->exists()) {
            return response()->json(['message' => 'لديك طلب قيد المراجعة.'], 422);
        }

        $data = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($student->suspensionRequests()
            ->where('status', SuspensionStatus::Rejected)
            ->where('semester_id', $data['semester_id'])
            ->exists()) {
            return response()->json(['message' => 'تم رفض طلب سابق لهذا الفصل.'], 422);
        }

        $req = SuspensionRequest::create([
            'student_id' => $student->id,
            'semester_id' => $data['semester_id'],
            'reason' => $data['reason'],
            'status' => SuspensionStatus::Pending,
        ]);

        return response()->json([
            'message' => 'تم تقديم الطلب',
            'request' => new SuspensionRequestResource($req->load('semester')),
        ], 201);
    }
}
