<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AddDropRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AddDropRequest */
class AddDropRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
            ],
            'status' => [
                'value' => $this->status,
                'label' => match ($this->status) {
                    'pending' => 'قيد المراجعة',
                    'approved' => 'مقبول',
                    'rejected' => 'مرفوض',
                    default => $this->status,
                },
            ],
            'reason' => $this->reason,
            'course_offering' => new CourseOfferingResource($this->whenLoaded('courseOffering')),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
