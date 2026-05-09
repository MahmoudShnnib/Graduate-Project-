<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ThesisRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThesisRegistration */
class ThesisResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'supervisor_name' => $this->getSupervisorDisplayName(),
            'is_external_supervisor' => (bool) $this->is_external_supervisor,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->studentLabel(),
                'color' => $this->status?->studentColor(),
            ],
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
        ];
    }
}
