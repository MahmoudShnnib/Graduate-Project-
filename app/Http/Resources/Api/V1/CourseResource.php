<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Course */
class CourseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'credits' => $this->credits,
            'is_active' => (bool) $this->is_active,
            'type' => $this->whenPivotLoaded('course_specialization', fn () => $this->pivot->type),
        ];
    }
}
