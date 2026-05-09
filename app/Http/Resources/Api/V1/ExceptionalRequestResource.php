<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ExceptionalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExceptionalRequest */
class ExceptionalRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'opportunity_type' => [
                'value' => $this->opportunity_type,
                'label' => match ($this->opportunity_type) {
                    'regular_failure' => 'فرصة رسوب عادي',
                    'attendance' => 'فرصة حضور',
                    default => null,
                },
            ],
            'reason' => $this->reason,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->getColor(),
            ],
            'decision_notes' => $this->decision_notes,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
