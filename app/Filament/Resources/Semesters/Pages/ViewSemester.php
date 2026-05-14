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
        $readyMidterm = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('midterm_published_at')->whereNotNull('coursework_grade')->count();
        $readyFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('published_at')->whereNotNull('total')->count();
        $noFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('total')->count();

        $totalEnrolled = \App\Models\StudentEnrollment::whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semesterId))
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled)->count();
        $noGrade = $totalEnrolled - $totalGrades;

        $midtermWarning = $noGrade > 0 ? "\n\n⚠️ تنبيه: يوجد {$noGrade} تسجيل بدون درجات مدخلة." : '';
        $finalWarning = '';
        if ($noGrade > 0) {
            $finalWarning .= "\n\n⚠️ يوجد {$noGrade} تسجيل بدون درجات مدخلة.";
        }
        if ($noFinal > 0) {
            $finalWarning .= "\n⚠️ يوجد {$noFinal} درجة بأعمال فصل فقط (بدون نهائي).";
        }

        return [
            Action::make('publish_midterms')
                ->label('نشر درجات أعمال الفصل')
                ->icon(Heroicon::OutlinedMegaphone)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('نشر درجات أعمال الفصل للفصل الدراسي')
                ->modalDescription(
                    "سيتم نشر درجات أعمال الفصل لـ {$readyMidterm} درجة من أصل {$totalEnrolled} تسجيل في الفصل."
                    ."\nسيتمكن الطلاب من رؤية درجات أعمال الفصل فقط."
                    .$midtermWarning
                )
                ->action(function (AuditService $audit): void {
                    $count = Grade::whereHas('enrollment.courseOffering', fn ($q) => $q->where('semester_id', $this->record->id))
                        ->whereHas('enrollment', fn ($q) => $q->where('status', \App\Enums\EnrollmentStatus::Enrolled))
                        ->where('status', GradeStatus::Approved)
                        ->whereNull('midterm_published_at')
                        ->whereNotNull('coursework_grade')
                        ->update(['midterm_published_at' => now()]);

                    $audit->log('grades_midterm_published', $this->record, newValues: ['count' => $count]);

                    Notification::make()->title("تم نشر درجات أعمال الفصل لـ {$count} درجة")->success()->send();
                }),

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
                            $grade->updateQuietly([
                                'midterm_published_at' => $grade->midterm_published_at ?? now(),
                                'published_at' => now(),
                            ]);
                            $studentIds->push($grade->enrollment->student_id);
                        }
                    });

                    $uniqueStudentIds = $studentIds->unique();
                    foreach ($uniqueStudentIds as $studentId) {
                        $student = Student::find($studentId);
                        if ($student) {
                            $calculator->recalculateStudentGPA($student);
                        }
                    }

                    foreach ($grades as $grade) {
                        $student = $grade->enrollment->student;
                        if ($student) {
                            $warningService->evaluateGradeApproval($student, $grade);
                        }
                        $notifier->notifyGradePublished($grade);
                    }

                    $audit->log('grades_published', $this->record, newValues: ['count' => $grades->count()]);

                    Notification::make()->title("تم نشر {$grades->count()} درجة نهائية وتحديث المعدلات")->success()->send();
                }),

            EditAction::make(),
        ];
    }
}
