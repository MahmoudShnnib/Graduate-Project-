<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CourseSurvey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseSurvey */
class SurveyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'priority' => $this->priority,
            'is_retake' => (bool) $this->is_retake,
            'is_processed' => (bool) $this->is_processed,
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
