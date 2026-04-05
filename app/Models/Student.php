<?php

namespace App\Models;

use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'enrollment_no',
        'specialization_id',
        'status',
        'gpa',
        'warnings_count',
        'suspension_count',
        'last_name',
        'mother_name',
        'birth_date',
        'birth_place',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'card_number',
        'residence',
        'permanent_address',
        'profession',
        'workplace',
        'study_type',
        'delegation_number',
        'delegation_entity',
        'is_transfer',
        'degree_image',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'gpa' => 'decimal:2',
            'warnings_count' => 'integer',
            'suspension_count' => 'integer',
            'is_transfer' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function courseSurveys(): HasMany
    {
        return $this->hasMany(CourseSurvey::class);
    }

    public function suspensionRequests(): HasMany
    {
        return $this->hasMany(SuspensionRequest::class);
    }

    public function thesisRegistration(): HasOne
    {
        return $this->hasOne(ThesisRegistration::class);
    }

    public function clearanceRequest(): HasOne
    {
        return $this->hasOne(ClearanceRequest::class);
    }

    public function completedCoursesCount(): int
    {
        return $this->enrollments()
            ->whereHas('grade', function ($query) {
                $query->where('status', GradeStatus::Approved)
                    ->whereNotNull('letter')
                    ->where('letter', '!=', 'F');
            })
            ->count();
    }

    public function isEligibleForThesis(): bool
    {
        return $this->completedCoursesCount() >= 10
            && (float) $this->gpa >= 3.0;
    }
}
