<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'student_number',
        'user_id',
        'specialization_id',
        'admission_year',
        'semester_type',
    ];

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }
    public static function generateStudentNumber(int $specializationCode, int $semesterType): string
    {
        $year = now()->format('y');

        $lastStudent = self::where('specialization_id', $specializationCode)
            ->latest()
            ->first();

        $sequence = $lastStudent ? ((int) substr($lastStudent->student_number, -2) + 1) : 1;

        $sequence = str_pad($sequence, 2, '0', STR_PAD_LEFT);

        return $year . $specializationCode . $semesterType . $sequence;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
