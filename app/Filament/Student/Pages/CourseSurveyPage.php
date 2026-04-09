<?php

namespace App\Filament\Student\Pages;

use App\Models\Course;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CourseSurveyPage extends Page
{
    protected string $view = 'filament.student.pages.course-survey';

    protected static ?string $navigationLabel = 'استطلاع المقررات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 3;

    public const int MAX_SELECTIONS = 4;

    /** @var array<int, int> course_id => priority */
    public array $selections = [];

    public function mount(): void
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if ($student && $activeSemester) {
            $existing = CourseSurvey::where('student_id', $student->id)
                ->where('semester_id', $activeSemester->id)
                ->get()
                ->keyBy('course_id');

            foreach ($existing as $courseId => $survey) {
                $this->selections[$courseId] = $survey->priority;
            }
        }
    }

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function getAvailableCourses(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        return Course::where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $activeSemester->id))
            ->whereHas('specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->with(['specializations' => fn ($q) => $q->where('specializations.id', $student->specialization_id)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Returns the enrollment status for each surveyed course in the active semester.
     * Possible values: 'enrolled' | 'pending' | 'unavailable'
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

        $statuses = [];
        foreach (array_keys($this->selections) as $courseId) {
            $offering = \App\Models\CourseOffering::where('course_id', $courseId)
                ->where('semester_id', $activeSemester->id)
                ->first();

            if (! $offering) {
                $statuses[$courseId] = 'unavailable';

                continue;
            }

            $enrolled = \App\Models\StudentEnrollment::where('student_id', $student->id)
                ->where('course_offering_id', $offering->id)
                ->exists();

            $statuses[$courseId] = $enrolled ? 'enrolled' : 'pending';
        }

        return $statuses;
    }

    public function isMaxReached(): bool
    {
        return count($this->selections) >= self::MAX_SELECTIONS;
    }

    public function toggleCourse(int $courseId): void
    {
        $semester = $this->getActiveSemester();
        if (! $semester?->survey_open) {
            Notification::make()->title('الاستطلاع مغلق حالياً')->warning()->send();

            return;
        }

        if (isset($this->selections[$courseId])) {
            // Deselect: remove then re-number remaining sequentially
            unset($this->selections[$courseId]);
            $sorted = collect($this->selections)->sortBy(fn ($p) => $p)->keys()->values();
            $this->selections = [];
            foreach ($sorted as $i => $id) {
                $this->selections[$id] = $i + 1;
            }
        } else {
            if (count($this->selections) >= self::MAX_SELECTIONS) {
                Notification::make()
                    ->title('الحد الأقصى '.self::MAX_SELECTIONS.' مقررات')
                    ->body('أزل مقرراً قبل إضافة آخر')
                    ->warning()
                    ->send();

                return;
            }

            $this->selections[$courseId] = count($this->selections) + 1;
        }
    }

    public function submit(): void
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            Notification::make()->title('لا يوجد فصل دراسي نشط')->danger()->send();

            return;
        }

        if (! $activeSemester->survey_open) {
            Notification::make()->title('الاستطلاع مغلق حالياً')->danger()->send();

            return;
        }

        if (count($this->selections) < 1 || count($this->selections) > self::MAX_SELECTIONS) {
            Notification::make()
                ->title('يجب اختيار مقرر واحد على الأقل (بحد أقصى '.self::MAX_SELECTIONS.')')
                ->body('اخترت '.count($this->selections).' حتى الآن')
                ->warning()
                ->send();

            return;
        }

        CourseSurvey::where('student_id', $student->id)
            ->where('semester_id', $activeSemester->id)
            ->delete();

        foreach ($this->selections as $courseId => $priority) {
            CourseSurvey::create([
                'student_id' => $student->id,
                'semester_id' => $activeSemester->id,
                'course_id' => $courseId,
                'priority' => $priority,
            ]);
        }

        Notification::make()->title('تم حفظ اختياراتك بنجاح')->success()->send();
    }
}
