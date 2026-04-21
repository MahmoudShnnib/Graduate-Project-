<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSurvey extends Model
{
    protected $fillable = [
        'student_id',
        'semester_id',
        'course_id',
        'priority',
        'is_processed',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_processed' => 'boolean',
            'rejected_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
