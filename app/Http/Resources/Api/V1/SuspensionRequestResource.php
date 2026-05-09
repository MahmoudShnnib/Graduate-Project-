<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SuspensionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SuspensionRequest */
class SuspensionRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'reason' => $this->reason,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'decision_notes' => $this->decision_notes,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
