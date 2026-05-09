<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Grade */
class GradeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $isPublished = $this->published_at !== null;
        $isMidtermPublished = $this->midterm_published_at !== null;

        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'coursework_grade' => $isMidtermPublished || $isPublished ? $this->coursework_grade : null,
            'final_grade' => $isPublished ? $this->final_grade : null,
            'total' => $isPublished ? $this->total : null,
            'letter' => $isPublished ? $this->letter : null,
            'points' => $isPublished ? (float) $this->points : null,
            'midterm_published_at' => $this->midterm_published_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'publish_state' => match (true) {
                $isPublished => 'published',
                $isMidtermPublished => 'midterm_only',
                default => 'unpublished',
            },
        ];
    }
}
