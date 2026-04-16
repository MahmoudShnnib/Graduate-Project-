<?php

namespace App\Filament\Pages;

use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class FacultyGradeEntry extends Page
{
    protected string $view = 'filament.pages.faculty-grade-entry';

    protected static ?string $navigationLabel = 'إدخال الدرجات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 10;

    public ?int $selectedOfferingId = null;

    public int $courseworkMax = 60;

    public int $finalMax = 40;

    /** @var array<int, array{coursework: string|null, final: string|null}> enrollment_id => grades */
    public array $gradeInputs = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'faculty';
    }

    public function getFacultyOfferings(): Collection
    {
        return CourseOffering::where('faculty_id', auth()->id())
            ->with('course', 'semester')
            ->get();
    }

    public function getSelectedOffering(): ?CourseOffering
    {
        if (! $this->selectedOfferingId) {
            return null;
        }

        return CourseOffering::find($this->selectedOfferingId);
    }

    public function getEnrollments(): Collection
    {
        if (! $this->selectedOfferingId) {
            return collect();
        }

        return StudentEnrollment::where('course_offering_id', $this->selectedOfferingId)
            ->with(['student.user', 'grade'])
            ->get();
    }

    public function updatedSelectedOfferingId(): void
    {
        $this->gradeInputs = [];

        $offering = $this->getSelectedOffering();

        if ($offering) {
            $this->courseworkMax = $offering->coursework_max;
            $this->finalMax = $offering->final_max;
        }

        foreach ($this->getEnrollments() as $enrollment) {
            $this->gradeInputs[$enrollment->id] = [
                'coursework' => $enrollment->grade?->coursework_grade !== null
                    ? (string) $enrollment->grade->coursework_grade
                    : null,
                'final' => $enrollment->grade?->final_grade !== null
                    ? (string) $enrollment->grade->final_grade
                    : null,
            ];
        }
    }

    public function saveDistribution(): void
    {
        $this->validate([
            'courseworkMax' => 'required|integer|min:1|max:99',
            'finalMax' => 'required|integer|min:1|max:99',
        ]);

        if (($this->courseworkMax + $this->finalMax) !== 100) {
            Notification::make()
                ->title('مجموع التوزيع يجب أن يساوي 100')
                ->danger()
                ->send();

            return;
        }

        $offering = $this->getSelectedOffering();

        if (! $offering) {
            return;
        }

        $offering->update([
            'coursework_max' => $this->courseworkMax,
            'final_max' => $this->finalMax,
        ]);

        Notification::make()
            ->title('تم حفظ توزيع الدرجات بنجاح')
            ->success()
            ->send();
    }

    public function saveGrades(GradeCalculationService $gradeService, NotificationService $notifier): void
    {
        if (! $this->selectedOfferingId) {
            return;
        }

        $this->validate(
            collect($this->gradeInputs)->flatMap(fn ($_, $id) => [
                "gradeInputs.{$id}.coursework" => "nullable|numeric|min:0|max:{$this->courseworkMax}",
                "gradeInputs.{$id}.final" => "nullable|numeric|min:0|max:{$this->finalMax}",
            ])->toArray(),
            [
                'gradeInputs.*.coursework.max' => "أعمال الفصل يجب ألا تتجاوز {$this->courseworkMax}",
                'gradeInputs.*.final.max' => "الاختبار النهائي يجب ألا يتجاوز {$this->finalMax}",
                'gradeInputs.*.coursework.min' => 'الدرجة لا يمكن أن تكون سالبة',
                'gradeInputs.*.final.min' => 'الدرجة لا يمكن أن تكون سالبة',
            ]
        );

        foreach ($this->gradeInputs as $enrollmentId => $inputs) {
            if ($inputs['coursework'] === null && $inputs['final'] === null) {
                continue;
            }

            $enrollment = StudentEnrollment::find($enrollmentId);

            if (! $enrollment) {
                continue;
            }

            Grade::updateOrCreate(
                ['enrollment_id' => $enrollmentId],
                [
                    'coursework_grade' => $inputs['coursework'],
                    'final_grade' => $inputs['final'],
                    'status' => 'draft',
                ]
            );
        }

        Notification::make()->title('تم حفظ الدرجات بنجاح')->success()->send();
    }
}
