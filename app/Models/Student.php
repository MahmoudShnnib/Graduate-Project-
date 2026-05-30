<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Enums\WarningType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'dismissed_at',
        'dismissal_type',
        'dismissal_course_id',
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
            'dismissed_at' => 'datetime',
            'dismissal_type' => WarningType::class,
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

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->where('status', EnrollmentStatus::Enrolled);
    }

    public function courseOfferings(): BelongsToMany
    {
        return $this->belongsToMany(CourseOffering::class, 'student_enrollments')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function activeCourseOfferings(): BelongsToMany
    {
        return $this->courseOfferings()
            ->wherePivot('status', EnrollmentStatus::Enrolled->value);
    }

    public function getRegistrationSemesterAttribute(): ?Semester
    {
        $semester = $this->user?->application?->semester;

        if ($semester) {
            return $semester;
        }

        return $this->enrollments()
            ->with('courseOffering.semester')
            ->oldest('id')
            ->first()
            ?->courseOffering
            ?->semester;
    }

    public function courseSurveys(): HasMany
    {
        return $this->hasMany(CourseSurvey::class);
    }

    public function suspensionRequests(): HasMany
    {
        return $this->hasMany(SuspensionRequest::class);
    }

    public function addDropRequests(): HasMany
    {
        return $this->hasMany(AddDropRequest::class);
    }

    /**
     * Returns true if this student has an approved suspension request for
     * the given semester. Per-semester suspension blocks survey submission,
     * add/drop requests, and new enrollments for that semester while leaving
     * the student active overall.
     */
    public function isSuspendedInSemester(Semester $semester): bool
    {
        return $this->suspensionRequests()
            ->where('semester_id', $semester->id)
            ->where('status', SuspensionStatus::Approved)
            ->exists();
    }

    public function thesisRegistrations(): HasMany
    {
        return $this->hasMany(ThesisRegistration::class);
    }

    public function latestThesisRegistration(): HasOne
    {
        return $this->hasOne(ThesisRegistration::class)->latestOfMany();
    }

    public function clearanceRequest(): HasOne
    {
        return $this->hasOne(ClearanceRequest::class);
    }

    public function transferCourses(): HasMany
    {
        return $this->hasMany(TransferCourse::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(StudentWarning::class);
    }

    public function activeWarnings(): HasMany
    {
        return $this->hasMany(StudentWarning::class)->where('is_active', true);
    }

    public function dismissalCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'dismissal_course_id');
    }

    public function exceptionalRequests(): HasMany
    {
        return $this->hasMany(ExceptionalRequest::class);
    }

    public function discussionRequests(): HasMany
    {
        return $this->hasMany(DiscussionRequest::class);
    }

    public function latestDiscussionRequest(): HasOne
    {
        return $this->hasOne(DiscussionRequest::class)->latestOfMany();
    }

    public function completedCoursesCount(): int
    {
        $enrollmentCount = $this->activeEnrollments()
            ->whereHas('grade', function ($query) {
                $query->whereNotNull('published_at')
                    ->whereNotNull('letter')
                    ->where('letter', '!=', 'F');
            })
            ->count();

        $transferCount = $this->transferCourses()
            ->whereNotNull('letter')
            ->where('letter', '!=', 'F')
            ->count();

        return $enrollmentCount + $transferCount;
    }

    public function isEligibleForThesis(): bool
    {
        if ((float) $this->gpa < 3.0 || ! $this->specialization_id) {
            return false;
        }

        $breakdown = $this->getThesisEligibilityBreakdown();

        return $breakdown['general']['completed'] >= $breakdown['general']['required']
            && $breakdown['specialized']['completed'] >= $breakdown['specialized']['required']
            && $breakdown['elective']['completed'] >= 2;
    }

    /** @return array{general: array{required: int, completed: int}, specialized: array{required: int, completed: int}, elective: array{required: int, completed: int}} */
    public function getThesisEligibilityBreakdown(): array
    {
        $completedCourseIds = $this->getCompletedCourseIds();

        $breakdown = [];
        foreach (['general', 'specialized', 'elective'] as $type) {
            $courseIds = Course::query()
                ->whereHas('specializations', fn ($q) => $q
                    ->where('specialization_id', $this->specialization_id)
                    ->where('type', $type))
                ->pluck('id')
                ->all();

            $breakdown[$type] = [
                'required' => $type === 'elective' ? 2 : count($courseIds),
                'completed' => count(array_intersect($courseIds, $completedCourseIds)),
            ];
        }

        return $breakdown;
    }

    /** @return int[] */
    public function getCompletedCourseIds(): array
    {
        $enrollmentIds = $this->enrollments()
            ->whereHas('grade', fn ($q) => $q
                ->whereNotNull('published_at')
                ->whereNotNull('letter')
                ->where('letter', '!=', 'F'))
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id')
            ->all();

        $transferIds = $this->transferCourses()
            ->whereNotNull('letter')
            ->where('letter', '!=', 'F')
            ->pluck('course_id')
            ->all();

        return array_values(array_unique(array_merge($enrollmentIds, $transferIds)));
    }

    /**
     * Single source of truth for "can this student submit / be written to".
     * Only Active students may perform actions. All other statuses are view-only.
     */
    public function canPerformActions(?Semester $semester = null): bool
    {
        return $this->status === StudentStatus::Active;
    }

    public function getDisplayStatusLabel(): string
    {
        return $this->status?->label() ?? '—';
    }

    public function getDisplayStatusColor(): string
    {
        return match ($this->status) {
            StudentStatus::Active => 'success',
            StudentStatus::OnHold => 'danger',
            StudentStatus::Inactive => 'warning',
            StudentStatus::Transferred => 'info',
            StudentStatus::Graduated => 'info',
            StudentStatus::Dismissed => 'danger',
            default => 'gray',
        };
    }
}
