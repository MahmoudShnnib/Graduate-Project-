<?php

namespace App\Models;

use App\Enums\GradeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'coursework_grade',
        'final_grade',
        'total',
        'letter',
        'points',
        'status',
        'paper_sheet_path',
        'approved_by',
        'approved_at',
        'midterm_published_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'coursework_grade' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'total' => 'decimal:2',
            'points' => 'decimal:1',
            'status' => GradeStatus::class,
            'approved_at' => 'datetime',
            'midterm_published_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    /**
     * Human label for the audit trail: "student name - course name", so a grade
     * row reads meaningfully instead of falling back to a bare record id.
     */
    public function auditSubjectLabel(): ?string
    {
        $student = $this->enrollment?->student?->user?->name
            ?? $this->enrollment?->student?->enrollment_no;
        $course = $this->enrollment?->courseOffering?->course?->name;

        return match (true) {
            $student && $course => $student.' - '.$course,
            (bool) $student => $student,
            (bool) $course => $course,
            default => null,
        };
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array{letter: string, points: float}
     */
    public static function convertToLetter(float $total): array
    {
        return match (true) {
            $total >= 85 => ['letter' => 'A', 'points' => 4.0],
            $total >= 75 => ['letter' => 'B+', 'points' => 3.5],
            $total >= 70 => ['letter' => 'B', 'points' => 3.0],
            $total >= 65 => ['letter' => 'C+', 'points' => 2.5],
            $total >= 60 => ['letter' => 'C', 'points' => 2.0],
            $total >= 55 => ['letter' => 'D+', 'points' => 1.5],
            $total >= 50 => ['letter' => 'D', 'points' => 1.0],
            default => ['letter' => 'F', 'points' => 0.0],
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Grade $grade) {
            if ($grade->coursework_grade !== null && $grade->final_grade !== null) {
                $grade->total = $grade->coursework_grade + $grade->final_grade;
                $conversion = self::convertToLetter((float) $grade->total);
                $grade->letter = $conversion['letter'];
                $grade->points = $conversion['points'];
            } else {
                $grade->total = null;
                $grade->letter = null;
                $grade->points = null;
            }
        });
    }
}
