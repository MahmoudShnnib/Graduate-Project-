<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferCourse extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'coursework_grade',
        'final_grade',
        'letter',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'coursework_grade' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'total' => 'decimal:2',
            'points' => 'decimal:1',
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

    protected static function booted(): void
    {
        static::saving(function (TransferCourse $transferCourse) {
            if ($transferCourse->coursework_grade !== null && $transferCourse->final_grade !== null) {
                $total = (float) $transferCourse->coursework_grade + (float) $transferCourse->final_grade;
                $conversion = Grade::convertToLetter($total);
                $transferCourse->letter = $conversion['letter'];
                $transferCourse->points = $conversion['points'];
            }
        });
    }
}
