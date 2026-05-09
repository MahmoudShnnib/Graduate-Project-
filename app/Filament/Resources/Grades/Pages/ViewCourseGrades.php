<?php

namespace App\Filament\Resources\Grades\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\GradeResource;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Services\AuditService;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
use App\Services\WarningService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ViewCourseGrades extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GradeResource::class;

    protected string $view = 'filament.resources.grades.pages.view-course-grades';

    public CourseOffering $record;

    public function boot(): void
    {
        $user = auth()->user();
        if (isset($this->record) && $user->role === 'faculty' && $this->record->faculty_id !== $user->id) {
            abort(403);
        }
    }

    public function getTitle(): string
    {
        return 'درجات مادة: '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->course->name;
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isFaculty = $user->role === 'faculty';

        if ($isFaculty) {
            return [];
        }

        $baseQuery = fn () => Grade::whereHas('enrollment', fn ($q) => $q->where('course_offering_id', $this->record->id)
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled));

        $totalEnrolled = \App\Models\StudentEnrollment::where('course_offering_id', $this->record->id)
            ->where('status', \App\Enums\EnrollmentStatus::Enrolled)->count();
        $withGrade = (clone $baseQuery)()->count();
        $noGrade = $totalEnrolled - $withGrade;

        $readyMidterm = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('midterm_published_at')->whereNotNull('coursework_grade')->count();
        $readyFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('published_at')->whereNotNull('total')->count();
        $approvedNoFinal = (clone $baseQuery)()->where('status', GradeStatus::Approved)
            ->whereNull('total')->count();

        $midtermWarning = $noGrade > 0
            ? "\n\n⚠️ تنبيه: يوجد {$noGrade} طالب مسجل بدون درجات مدخلة."
            : '';
        $finalWarning = '';
        if ($noGrade > 0) {
            $finalWarning .= "\n\n⚠️ يوجد {$noGrade} طالب بدون درجات مدخلة.";
        }
        if ($approvedNoFinal > 0) {
            $finalWarning .= "\n⚠️ يوجد {$approvedNoFinal} طالب بدرجات أعمال فصل فقط (بدون نهائي).";
        }

        return [
            Action::make('publish_midterms')
                ->label('نشر درجات أعمال الفصل')
                ->icon(Heroicon::OutlinedMegaphone)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('نشر درجات أعمال الفصل')
                ->modalDescription(
                    "سيتم نشر درجات أعمال الفصل لـ {$readyMidterm} طالب من أصل {$totalEnrolled} مسجل."
                    ."\nسيتمكن الطلاب من رؤية درجات أعمال الفصل فقط."
                    .$midtermWarning
                )
                ->action(function (AuditService $audit): void {
                    $count = Grade::whereHas('enrollment', fn ($q) => $q->where('course_offering_id', $this->record->id)
                        ->where('status', \App\Enums\EnrollmentStatus::Enrolled))
                        ->where('status', GradeStatus::Approved)
                        ->whereNull('midterm_published_at')
                        ->whereNotNull('coursework_grade')
                        ->update(['midterm_published_at' => now()]);

                    $audit->log('grades_midterm_published', $this->record, newValues: ['count' => $count]);

                    Notification::make()->title("تم نشر درجات أعمال الفصل لـ {$count} طالب")->success()->send();
                }),

            Action::make('publish_finals')
                ->label('نشر الدرجات النهائية')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('نشر الدرجات النهائية')
                ->modalDescription(
                    "سيتم نشر الدرجات النهائية لـ {$readyFinal} طالب من أصل {$totalEnrolled} مسجل."
                    ."\nسيتم تحديث المعدل التراكمي وتفعيل التحذيرات."
                    .$finalWarning
                )
                ->action(function (GradeCalculationService $calculator, WarningService $warningService, NotificationService $notifier, AuditService $audit): void {
                    $grades = Grade::whereHas('enrollment', fn ($q) => $q->where('course_offering_id', $this->record->id)
                        ->where('status', \App\Enums\EnrollmentStatus::Enrolled))
                        ->where('status', GradeStatus::Approved)
                        ->whereNull('published_at')
                        ->whereNotNull('total')
                        ->with(['enrollment.student'])
                        ->get();

                    if ($grades->isEmpty()) {
                        Notification::make()->title('لا توجد درجات جاهزة للنشر')->warning()->send();

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
                        $student = \App\Models\Student::find($studentId);
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
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Grade::query()
                    ->whereHas('enrollment', fn ($q) => $q->where('course_offering_id', $this->record->id)
                        ->where('status', \App\Enums\EnrollmentStatus::Enrolled))
                    ->with(['enrollment.student.user'])
            )
            ->columns([
                TextColumn::make('enrollment.student.enrollment_no')
                    ->label('رقم القيد')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('enrollment.student.user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('coursework_grade')
                    ->label('أعمال الفصل')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('final_grade')
                    ->label('الامتحان النهائي')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('total')
                    ->label('المجموع')
                    ->numeric(2)
                    ->placeholder('—'),

                TextColumn::make('letter')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B+', 'B' => 'info',
                        'C+', 'C' => 'warning',
                        'D+', 'D' => 'gray',
                        default => 'danger',
                    })
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (GradeStatus $state): string => $state->label())
                    ->color(fn (GradeStatus $state): string => match ($state) {
                        GradeStatus::Draft => 'gray',
                        GradeStatus::PendingApproval => 'warning',
                        GradeStatus::Approved => 'info',
                    }),

                TextColumn::make('publish_status')
                    ->label('النشر')
                    ->badge()
                    ->state(function (Grade $record): string {
                        if ($record->published_at) {
                            return 'منشور';
                        }
                        if ($record->midterm_published_at) {
                            return 'منتصف فقط';
                        }

                        return 'غير منشور';
                    })
                    ->color(function (Grade $record): string {
                        if ($record->published_at) {
                            return 'success';
                        }
                        if ($record->midterm_published_at) {
                            return 'warning';
                        }

                        return 'gray';
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('اعتماد')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (Grade $record): bool => $record->status !== GradeStatus::Approved)
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الدرجة')
                    ->modalDescription('هل تريد اعتماد هذه الدرجة؟')
                    ->action(function (Grade $record, AuditService $audit): void {
                        $record->update([
                            'status' => GradeStatus::Approved,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        $audit->log('grade_approved', $record, newValues: ['letter' => $record->letter, 'total' => $record->total]);

                        Notification::make()->title('تم اعتماد الدرجة بنجاح')->success()->send();
                    }),

                Action::make('change_status')
                    ->label('تغيير الحالة')
                    ->icon(Heroicon::ArrowPath)
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('الحالة الجديدة')
                            ->options([
                                GradeStatus::Draft->value => GradeStatus::Draft->label(),
                                GradeStatus::PendingApproval->value => GradeStatus::PendingApproval->label(),
                                GradeStatus::Approved->value => GradeStatus::Approved->label(),
                            ])
                            ->required(),
                    ])
                    ->fillForm(fn (Grade $record): array => [
                        'status' => $record->status->value,
                    ])
                    ->action(function (Grade $record, array $data): void {
                        $newStatus = GradeStatus::from($data['status']);
                        $wasApproved = $record->status === GradeStatus::Approved;

                        $updateData = ['status' => $newStatus];

                        if ($newStatus === GradeStatus::Approved && ! $wasApproved) {
                            $updateData['approved_by'] = auth()->id();
                            $updateData['approved_at'] = now();
                        }

                        $record->update($updateData);

                        Notification::make()->title('تم تغيير الحالة بنجاح')->success()->send();
                    }),

                Action::make('edit_grade')
                    ->label('تعديل')
                    ->icon(Heroicon::Pencil)
                    ->color('gray')
                    ->visible(fn (Grade $record): bool => $record->status !== GradeStatus::Approved || auth()->user()->role === 'super_admin')
                    ->modalHeading('تعديل الدرجة')
                    ->form(fn (): array => [
                        TextInput::make('coursework_grade')
                            ->label('درجة أعمال الفصل (/'.$this->record->coursework_max.')')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->coursework_max)
                            ->required(),

                        TextInput::make('final_grade')
                            ->label('درجة الامتحان النهائي (/'.$this->record->final_max.')')
                            ->placeholder('اتركه فارغاً إذا لم يتم الامتحان بعد')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->final_max),
                    ])
                    ->fillForm(fn (Grade $record): array => [
                        'coursework_grade' => $record->coursework_grade,
                        'final_grade' => $record->final_grade,
                    ])
                    ->action(function (Grade $record, array $data): void {
                        $finalGrade = $data['final_grade'] !== null && $data['final_grade'] !== '' ? $data['final_grade'] : null;

                        $record->update([
                            'coursework_grade' => $data['coursework_grade'],
                            'final_grade' => $finalGrade,
                        ]);

                        Notification::make()->title('تم تعديل الدرجة')->success()->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('approve_all')
                    ->label('اعتماد المحددين')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الدرجات المحددة')
                    ->modalDescription('سيتم اعتماد جميع الدرجات المحددة.')
                    ->action(function (Collection $records): void {
                        $count = 0;

                        foreach ($records as $grade) {
                            if ($grade->status === GradeStatus::Approved) {
                                continue;
                            }

                            $grade->update([
                                'status' => GradeStatus::Approved,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ]);

                            $count++;
                        }

                        Notification::make()->title("تم اعتماد {$count} درجة")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('لا توجد درجات مدخلة')
            ->emptyStateDescription('لم يقم عضو هيئة التدريس بإدخال الدرجات بعد.');
    }
}
