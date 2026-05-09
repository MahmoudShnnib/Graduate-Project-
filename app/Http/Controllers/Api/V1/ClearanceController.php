<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DiscussionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClearanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()->student->load('clearanceRequest', 'specialization', 'latestThesisRegistration');
        $discussion = $student->latestDiscussionRequest;

        $eligible = $discussion && in_array($discussion->status, [
            DiscussionStatus::DiscussionApproved,
            DiscussionStatus::Completed,
        ], true);

        return response()->json([
            'eligible' => $eligible,
            'request' => $student->clearanceRequest ? new ClearanceResource($student->clearanceRequest) : null,
            'discussion_phase' => $discussion?->status?->value,
            'student' => [
                'name' => $student->user?->name,
                'enrollment_no' => $student->enrollment_no,
                'specialization' => $student->specialization?->name,
                'supervisor_name' => $student->latestThesisRegistration?->getSupervisorDisplayName(),
            ],
        ]);
    }
}
