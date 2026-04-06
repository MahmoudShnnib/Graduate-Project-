<?php

namespace App\Models;

use App\Enums\Season;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'season',
        'start_date',
        'end_date',
        'add_drop_start',
        'add_drop_end',
        'is_active',
        'survey_open',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'season' => Season::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'add_drop_start' => 'date',
            'add_drop_end' => 'date',
            'is_active' => 'boolean',
            'survey_open' => 'boolean',
        ];
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(CourseSurvey::class);
    }

    public function isAddDropOpen(): bool
    {
        if (! $this->add_drop_start || ! $this->add_drop_end) {
            return false;
        }

        $today = now()->toDateString();

        return $today >= $this->add_drop_start->toDateString()
            && $today <= $this->add_drop_end->toDateString();
    }
}
