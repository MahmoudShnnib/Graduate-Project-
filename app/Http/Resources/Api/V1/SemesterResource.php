<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Semester */
class SemesterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'year' => $this->year,
            'season' => [
                'value' => $this->season?->value,
                'label' => $this->season?->label(),
            ],
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'survey_open' => (bool) $this->survey_open,
            'add_drop_start' => $this->add_drop_start?->toDateString(),
            'add_drop_end' => $this->add_drop_end?->toDateString(),
            'add_drop_open' => $this->isAddDropOpen(),
        ];
    }
}
