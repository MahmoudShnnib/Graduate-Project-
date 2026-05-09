<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DiscussionStatus;
use App\Enums\ThesisStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DiscussionResource;
use App\Http\Resources\Api\V1\ThesisResource;
use App\Models\ThesisRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThesisController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $registration = $student->latestThesisRegistration;
        $discussion = $student->latestDiscussionRequest;

        $phase = $this->derivePhase($registration, $discussion);
        $eligibility = $this->buildEligibility($student);

        return response()->json([
            'eligibility' => $eligibility,
            'registration' => $registration ? new ThesisResource($registration) : null,
            'discussion' => $discussion ? new DiscussionResource($discussion) : null,
            'phase' => $phase,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        if (! $student->canPerformActions() || ! $student->isEligibleForThesis()) {
            return response()->json(['message' => 'غير مستوفٍ لشروط تسجيل الرسالة.'], 422);
        }

        if ($student->thesisRegistrations()
            ->whereIn('status', [ThesisStatus::Pending, ThesisStatus::Printed, ThesisStatus::Approved])
            ->exists()) {
            return response()->json(['message' => 'لديك تسجيل رسالة سابق.'], 422);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'title_en' => ['nullable', 'string', 'max:300'],
            'is_external_supervisor' => ['required', 'boolean'],
            'supervisor_name' => ['required_if:is_external_supervisor,true', 'string', 'max:200'],
            'supervisor_employer' => ['nullable', 'string', 'max:200'],
            'supervisor_degree' => ['nullable', 'string', 'max:100'],
            'faculty_supervisor_id' => ['required_if:is_external_supervisor,false', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $reg = ThesisRegistration::create($data + [
            'student_id' => $student->id,
            'status' => ThesisStatus::Pending,
        ]);

        return response()->json([
            'message' => 'تم تسجيل الرسالة',
            'registration' => new ThesisResource($reg),
        ], 201);
    }

    /** @return array<string, mixed> */
    private function buildEligibility($student): array
    {
        $breakdown = $student->getThesisEligibilityBreakdown();
        $hasFailures = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at')->where('letter', 'F'))
            ->exists();

        return [
            'eligible' => $student->isEligibleForThesis(),
            'gpa' => [
                'value' => (float) $student->gpa,
                'required' => 3.0,
                'met' => (float) $student->gpa >= 3.0,
            ],
            'general' => $breakdown['general'],
            'specialized' => $breakdown['specialized'],
            'elective' => [
                'completed' => $breakdown['elective']['completed'],
                'required' => 2,
            ],
            'no_failures' => ! $hasFailures,
        ];
    }

    private function derivePhase($registration, $discussion): int
    {
        if (! $registration) {
            return 0;
        }
        if (! $discussion) {
            return 1;
        }
        if (in_array($discussion->status, [DiscussionStatus::DiscussionApproved, DiscussionStatus::Completed], true)) {
            return 4;
        }
        if (in_array($discussion->status, [DiscussionStatus::Discussed, DiscussionStatus::AcceptedWithEdits], true)) {
            return 3;
        }

        return 2;
    }
}
