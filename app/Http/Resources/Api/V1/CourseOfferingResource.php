<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CourseOffering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseOffering */
class CourseOfferingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'name' => $this->course->name,
                'code' => $this->course->code,
                'credits' => $this->course->credits,
            ]),
            'semester_id' => $this->semester_id,
            'faculty' => $this->whenLoaded('faculty', fn () => $this->faculty ? [
                'id' => $this->faculty->id,
                'name' => $this->faculty->name,
            ] : null),
            'is_open' => (bool) $this->is_open,
            'coursework_max' => (int) $this->coursework_max,
            'final_max' => (int) $this->final_max,
        ];
    }
}
