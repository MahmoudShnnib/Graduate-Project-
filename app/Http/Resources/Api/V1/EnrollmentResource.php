<?php

namespace App\Http\Resources\Api\V1;

use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StudentEnrollment */
class EnrollmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'course_offering' => new CourseOfferingResource($this->whenLoaded('courseOffering')),
            'grade' => new GradeResource($this->whenLoaded('grade')),
        ];
    }
}
