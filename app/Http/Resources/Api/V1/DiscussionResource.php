<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DiscussionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DiscussionRequest */
class DiscussionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thesis_registration_id' => $this->thesis_registration_id,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->studentLabel(),
                'color' => $this->status?->studentColor(),
            ],
            'discussion_date' => $this->discussion_date?->toDateString(),
            'rejection_reason' => $this->rejection_reason,
            'discussed_at' => $this->discussed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
