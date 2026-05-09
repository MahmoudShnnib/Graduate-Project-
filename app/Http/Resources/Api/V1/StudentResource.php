<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_no' => $this->enrollment_no,
            'name' => $this->user?->name,
            'name_en' => $this->user?->name_en,
            'email' => $this->user?->email,
            'phone' => $this->user?->phone,
            'national_id' => $this->national_id,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'admission_year' => $this->created_at?->year,
            'is_transfer' => (bool) $this->is_transfer,
            'gpa' => (float) $this->gpa,
            'specialization' => $this->whenLoaded('specialization', fn () => [
                'id' => $this->specialization->id,
                'name' => $this->specialization->name,
                'code' => $this->specialization->code,
            ]),
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->getDisplayStatusColor(),
            ],
            'warnings_count' => (int) $this->warnings_count,
            'active_warnings' => $this->whenLoaded('activeWarnings', fn () => WarningResource::collection($this->activeWarnings)),
            'can_perform_actions' => $this->canPerformActions(),
        ];
    }
}
