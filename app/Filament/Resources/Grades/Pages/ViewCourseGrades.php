<?php

namespace App\Filament\Resources\Grades\Pages;

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\GradeResource;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use App\Services\NotificationService;
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

    public function getTitle(): string
    {
        return 'درجات مادة: '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->course->name;
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
                        GradeStatus::Approved => 'success',
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
                    ->modalDescription('هل تريد اعتماد هذه الدرجة؟ لن يمكن تعديلها بعد الاعتماد.')
                    ->action(function (Grade $record, GradeCalculationService $calculator, NotificationService $notifier): void {
                        $record->update([
                            'status' => GradeStatus::Approved,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        $student = $record->enrollment->student;
                        $calculator->recalculateStudentGPA($student);
                        $notifier->notifyGradePublished($record);

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
                    ->action(function (Grade $record, array $data, GradeCalculationService $calculator, NotificationService $notifier): void {
                        $newStatus = GradeStatus::from($data['status']);
                        $wasApproved = $record->status === GradeStatus::Approved;

                        $updateData = ['status' => $newStatus];

                        if ($newStatus === GradeStatus::Approved && ! $wasApproved) {
                            $updateData['approved_by'] = auth()->id();
                            $updateData['approved_at'] = now();
                        }

                        $record->update($updateData);

                        if ($newStatus === GradeStatus::Approved && ! $wasApproved) {
                            $student = $record->enrollment->student;
                            $calculator->recalculateStudentGPA($student);
                            $notifier->notifyGradePublished($record);
                        }

                        Notification::make()->title('تم تغيير الحالة بنجاح')->success()->send();
                    }),

                Action::make('edit_grade')
                    ->label('تعديل')
                    ->icon(Heroicon::Pencil)
                    ->color('gray')
                    ->visible(fn (Grade $record): bool => $record->status !== GradeStatus::Approved)
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
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($this->record->final_max)
                            ->required(),
                    ])
                    ->fillForm(fn (Grade $record): array => [
                        'coursework_grade' => $record->coursework_grade,
                        'final_grade' => $record->final_grade,
                    ])
                    ->action(function (Grade $record, array $data): void {
                        $record->update([
                            'coursework_grade' => $data['coursework_grade'],
                            'final_grade' => $data['final_grade'],
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
                    ->modalDescription('سيتم اعتماد جميع الدرجات المحددة. لن يمكن تعديلها بعد الاعتماد.')
                    ->action(function (Collection $records, GradeCalculationService $calculator, NotificationService $notifier): void {
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

                            $student = $grade->enrollment->student;
                            $calculator->recalculateStudentGPA($student);
                            $notifier->notifyGradePublished($grade);
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
