<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Semesters\SemesterResource;
use App\Models\Grade;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
use App\Services\WarningService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSemester extends ViewRecord
{
    protected static string $resource = SemesterResource::class;

    protected string $view = 'filament.resources.semesters.pages.view-semester';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return $this->record->name ?? (string) $this->record->getKey();
    }

    protected function getHeaderActions(): array
    {
        if ($this->record->isArchived()) {
            return [];
        }

        $semesterId = $this->record->id;
        $baseQuery = fn () => Grade::whereHas('enrollment.courseOffering', fn ($q) => $q->where('semester_id', $semesterId))
            ->whereHas('enrollment', fn ($q) => $q->where('status', \App\Enums\EnrollmentStatus::Enrolled));

        $totalGrades = (clone $baseQuery)()->count();
        $readyFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('published_at')->whereNotNull('total')->count();
        $noFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('total')->count();

        $totalEnrolled = \App\Models\StudentEnrollment::whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semesterId))
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled)->count();
        $noGrade = $totalEnrolled - $totalGrades;

        $finalWarning = '';
        if ($noGrade > 0) {
            $finalWarning .= "\n\n⚠️ يوجد {$noGrade} تسجيل بدون درجات مدخلة.";
        }
        if ($noFinal > 0) {
            $finalWarning .= "\n⚠️ يوجد {$noFinal} درجة بأعمال فصل فقط (بدون نهائي).";
        }

        return [
            Action::make('publish_finals')
                ->label('نشر الدرجات النهائية')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('نشر الدرجات النهائية للفصل الدراسي')
                ->modalDescription(
                    "سيتم نشر الدرجات النهائية لـ {$readyFinal} درجة من أصل {$totalEnrolled} تسجيل."
                    ."\nسيتم تحديث المعدلات التراكمية وتفعيل التحذيرات."
                    .$finalWarning
                )
                ->action(function (GradeCalculationService $calculator, WarningService $warningService, NotificationService $notifier, AuditService $audit): void {
                    $grades = Grade::whereHas('enrollment.courseOffering', fn ($q) => $q->where('semester_id', $this->record->id))
                        ->whereHas('enrollment', fn ($q) => $q->where('status', \App\Enums\EnrollmentStatus::Enrolled))
                        ->where('status', GradeStatus::Approved)
                        ->whereNull('published_at')
                        ->whereNotNull('total')
                        ->with(['enrollment.student'])
                        ->get();

                    if ($grades->isEmpty()) {
                        Notification::make()->title('لا توجد درجات جاهزة للنشر النهائي')->warning()->send();

                        return;
                    }

                    $studentIds = collect();

                    Grade::withoutEvents(function () use ($grades, &$studentIds) {
                        foreach ($grades as $grade) {
                            $grade->updateQuietly(['published_at' => now()]);
                            $studentIds->push($grade->enrollment->student_id);
                        }
                    });

                    $uniqueStudentIds = $studentIds->unique();
                    $notifier->batch(function () use ($uniqueStudentIds, $calculator, $notifier) {
                        foreach ($uniqueStudentIds as $studentId) {
                            $student = Student::find($studentId);
                            if ($student) {
                                $calculator->recalculateStudentGPA($student);
                                // One general "grades published" message per student.
                                $notifier->notifyGradesPublished($student);
                            }
                        }
                    });

                    foreach ($grades as $grade) {
                        $student = $grade->enrollment->student;
                        if ($student) {
                            $warningService->evaluateGradeApproval($student, $grade);
                        }
                    }

                    $audit->log('grades_published', $this->record, newValues: ['count' => $grades->count()]);

                    Notification::make()->title("تم نشر {$grades->count()} درجة نهائية وتحديث المعدلات")->success()->send();
                }),

            EditAction::make(),
        ];
    }
}
