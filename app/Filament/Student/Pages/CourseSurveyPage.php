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

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

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

        if (! $student) {
            return collect();
        }

        return Course::where('is_active', true)
            ->whereHas('specializations', fn ($q) => $q->where('specializations.id', $student->specialization_id))
            ->with(['specializations' => fn ($q) => $q->where('specializations.id', $student->specialization_id)])
            ->orderBy('name')
            ->get();
    }

    public function toggleCourse(int $courseId): void
    {
        if (isset($this->selections[$courseId])) {
            unset($this->selections[$courseId]);
        } else {
            $max = $this->selections ? max($this->selections) : 0;
            $this->selections[$courseId] = $max + 1;
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
