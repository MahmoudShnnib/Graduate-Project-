<?php

namespace App\Models;

use App\Enums\WarningType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'course_id',
        'semester_id',
        'is_active',
        'resolved_at',
        'has_exceptional_chance',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarningType::class,
            'is_active' => 'boolean',
            'resolved_at' => 'datetime',
            'has_exceptional_chance' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
