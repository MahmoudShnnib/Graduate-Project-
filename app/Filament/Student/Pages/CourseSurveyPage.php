<?php

namespace App\Filament\Student\Pages;

use App\Models\Course;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Services\AuditService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CourseSurveyPage extends Page
{
    protected string $view = 'filament.student.pages.course-survey';

    protected static ?string $navigationLabel = 'استطلاع المقررات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 5;

    /** @var array<int, int> course_id => priority */
    public array $selections = [];

    public ?int $retakeConfirmCourseId = null;

    public bool $confirmingSubmit = false;

    public function mount(): void
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if ($student && $activeSemester) {
            $existing = CourseSurvey::where('student_id', $student->id)
                ->where('semester_id', $activeSemester->id)
                ->where('is_retake', false)
                ->get()
                ->keyBy('course_id');

            foreach ($existing as $courseId => $survey) {
                $this->selections[$courseId] = $survey->priority;
            }
        }
    }

    private ?Semester $resolvedSemester = null;

    public function getActiveSemester(): ?Semester
    {
        return $this->resolvedSemester ??= Semester::active();
    }

    public function getAvailableCourses(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        // A passed course — whether studied in-system or entered as a transfer
        // (معادلة) equivalence — is not offered again in the survey. It can only be
        // re-studied via the retake list (getRetakeableCourses).
        return Course::where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $activeSemester->id))
            ->whereHas('specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->whereNotIn('id', $student->getCompletedCourseIds())
            ->with(['specializations' => fn ($q) => $q->where('specializations.id', $student->specialization_id)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Returns the enrollment status for each surveyed course in the active semester.
     * Possible values: 'enrolled' | 'pending' | 'rejected'
     *
     * A surveyed choice stays 'pending' (قيد المراجعة) until the admin either
     * enrolls the student or rejects the choice. The course offering usually does
     * NOT exist yet at survey time — that is the whole point of the survey — so a
     * missing offering must read as "under review", never as "unavailable".
     *
     * @return array<int, string>
     */
    public function getEnrollmentStatuses(): array
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester || empty($this->selections)) {
            return [];
        }

        $surveys = CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->whereIn('course_id', array_keys($this->selections))
            ->get()
            ->keyBy('course_id');

        $statuses = [];
        foreach (array_keys($this->selections) as $courseId) {
            // Rejected takes precedence so the student sees the final decision.
            if ($surveys->get($courseId)?->rejected_at !== null) {
                $statuses[$courseId] = 'rejected';

                continue;
            }

            // Enrolled once the admin opens an offering and registers the student.
            // Anything before that — including no offering yet — is "under review".
            $enrolled = \App\Models\StudentEnrollment::where('student_id', $student->id)
                ->whereHas('courseOffering', fn ($q) => $q
                    ->where('course_id', $courseId)
                    ->where('semester_id', $activeSemester->id))
                ->active()
                ->exists();

            $statuses[$courseId] = $enrolled ? 'enrolled' : 'pending';
        }

        return $statuses;
    }

    /**
     * Whether the student has already submitted (finalized) their course survey
     * for the active semester. The survey is a once-per-semester decision — after
     * submitting, selections are locked. Retake requests are tracked separately.
     */
    public function hasSubmittedSurvey(): bool
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return false;
        }

        return CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->where('is_retake', false)
            ->exists();
    }

    public function toggleCourse(int $courseId): void
    {
        $semester = $this->getActiveSemester();
        if (! $semester?->survey_open) {
            Notification::make()->title('الاستطلاع مغلق حالياً')->warning()->send();

            return;
        }

        if ($this->hasSubmittedSurvey()) {
            Notification::make()->title('لقد أرسلت اختياراتك ولا يمكن تعديلها')->warning()->send();

            return;
        }

        if (isset($this->selections[$courseId])) {
            // Deselect: remove then re-number remaining sequentially so
            // priority 1..N is always contiguous.
            unset($this->selections[$courseId]);
            $sorted = collect($this->selections)->sortBy(fn ($p) => $p)->keys()->values();
            $this->selections = [];
            foreach ($sorted as $i => $id) {
                $this->selections[$id] = $i + 1;
            }
        } else {
            // No upper cap — the survey is a wishlist. Admin decides which
            // to actually enroll; hard 4-cap lives at the enrollment action
            // layer (EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER).
            $this->selections[$courseId] = count($this->selections) + 1;
        }
    }

    /** Open the confirmation modal warning the student that submitting is final. */
    public function confirmSubmit(): void
    {
        if (count($this->selections) < 1) {
            Notification::make()
                ->title('يجب اختيار مقرر واحد على الأقل')
                ->warning()
                ->send();

            return;
        }

        $this->confirmingSubmit = true;
    }

    public function cancelSubmit(): void
    {
        $this->confirmingSubmit = false;
    }

    public function submit(): void
    {
        $this->confirmingSubmit = false;

        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            Notification::make()->title('لا يوجد فصل دراسي نشط')->danger()->send();

            return;
        }

        // Once-per-semester: a finalized survey cannot be edited or resubmitted.
        if ($this->hasSubmittedSurvey()) {
            Notification::make()
                ->title('لقد أرسلت اختياراتك مسبقاً')
                ->body('لا يمكن تعديل الاستطلاع بعد إرساله.')
                ->warning()
                ->send();

            return;
        }

        if (! $student->canPerformActions($activeSemester)) {
            Notification::make()
                ->title('لا يمكنك تقديم هذا الطلب')
                ->body('حسابك ليس في حالة نشطة. لا يمكنك تقديم هذا الطلب.')
                ->danger()
                ->send();

            return;
        }

        if (! $activeSemester->survey_open) {
            Notification::make()->title('الاستطلاع مغلق حالياً')->danger()->send();

            return;
        }

        if (count($this->selections) < 1) {
            Notification::make()
                ->title('يجب اختيار مقرر واحد على الأقل')
                ->warning()
                ->send();

            return;
        }

        // Clear only the regular (non-retake) rows — retake requests are managed
        // separately and must survive. On a first submit there is nothing to clear.
        CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->where('is_retake', false)
            ->delete();

        foreach ($this->selections as $courseId => $priority) {
            CourseSurvey::create([
                'student_id' => $student->id,
                'semester_id' => $activeSemester->id,
                'course_id' => $courseId,
                'priority' => $priority,
                'is_retake' => false,
            ]);
        }

        app(AuditService::class)->log('survey_submitted', $student, newValues: ['count' => count($this->selections)]);

        Notification::make()->title('تم حفظ اختياراتك بنجاح')->success()->send();
    }

    /** Courses the student passed with a grade below A — eligible for retake. */
    public function getRetakeableCourses(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        $nonACourseIds = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q
                ->whereNotNull('published_at')
                ->whereNotNull('letter')
                ->where('letter', '!=', 'F')
                ->where('letter', '!=', 'A'))
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id');

        // Transfer (معادلة) courses are passed courses too — retakeable below grade A,
        // mirroring the rule for regular passes. If retaken, the new grade supersedes
        // the equivalence in the GPA (see GradeCalculationService).
        $transferNonACourseIds = $student->transferCourses()
            ->whereNotNull('letter')
            ->where('letter', '!=', 'F')
            ->where('letter', '!=', 'A')
            ->pluck('course_id');

        $nonACourseIds = $nonACourseIds->merge($transferNonACourseIds)->unique()->values();

        if ($nonACourseIds->isEmpty()) {
            return collect();
        }

        // Exclude courses the student is currently enrolled in this semester
        $currentlyEnrolledCourseIds = $student->activeEnrollments()
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->with('courseOffering:id,course_id')
            ->get()
            ->pluck('courseOffering.course_id')
            ->all();

        return Course::whereIn('id', $nonACourseIds)
            ->whereNotIn('id', $currentlyEnrolledCourseIds)
            ->where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $activeSemester->id))
            ->orderBy('name')
            ->get();
    }

    /** Get existing retake requests for the active semester. */
    public function getRetakeRequests(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        return CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->where('is_retake', true)
            ->with('course')
            ->get();
    }

    public function confirmRetake(int $courseId): void
    {
        $this->retakeConfirmCourseId = $courseId;
    }

    public function cancelRetake(): void
    {
        $this->retakeConfirmCourseId = null;
    }

    public function submitRetake(int $courseId): void
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return;
        }

        if (! $student->canPerformActions($activeSemester)) {
            Notification::make()
                ->title('لا يمكنك تقديم هذا الطلب')
                ->body('حسابك ليس في حالة نشطة.')
                ->danger()
                ->send();

            return;
        }

        if (! $activeSemester->survey_open) {
            Notification::make()->title('الاستطلاع مغلق حالياً')->danger()->send();

            return;
        }

        $exists = CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->where('course_id', $courseId)
            ->where('is_retake', true)
            ->exists();

        if ($exists) {
            Notification::make()->title('تم تقديم طلب إعادة لهذه المادة مسبقاً')->warning()->send();

            return;
        }

        CourseSurvey::create([
            'student_id' => $student->id,
            'semester_id' => $activeSemester->id,
            'course_id' => $courseId,
            'priority' => 0,
            'is_retake' => true,
        ]);

        $this->retakeConfirmCourseId = null;
        Notification::make()->title('تم تقديم طلب الإعادة بنجاح')->success()->send();
    }
}
