<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'semester_id',
        'faculty_id',
        'is_open',
        'coursework_max',
        'final_max',
        'time_slot',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'coursework_max' => 'integer',
            'final_max' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Human label for the audit trail: "course name - semester", so grade
     * submission/publish rows (logged against the offering) read meaningfully.
     */
    public function auditSubjectLabel(): ?string
    {
        $course = $this->course?->name;
        $semester = $this->semester?->name;

        return match (true) {
            $course && $semester => $course.' - '.$semester,
            (bool) $course => $course,
            default => null,
        };
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->where('status', EnrollmentStatus::Enrolled);
    }
}
