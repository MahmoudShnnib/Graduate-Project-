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
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
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
            }
        });
    }
}
