<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnrollStudents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseOfferingResource::class;

    protected string $view = 'filament.resources.course-offerings.pages.enroll-students';

    public CourseOffering $record;

    public function getTitle(): string
    {
        return 'تسجيل الطلاب — '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return 'تسجيل الطلاب';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enroll_all')
                ->label('تسجيل الجميع')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تسجيل جميع الطلاب')
                ->modalDescription('سيتم تسجيل كل الطلاب الذين اختاروا هذه المادة في الاستطلاع.')
                ->action(function () {
                    $count = $this->enrollAll();
                    Notification::make()
                        ->title("تم تسجيل {$count} طالب بنجاح")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $offeringId = $this->record->id;
        $courseId = $this->record->course_id;
        $semesterId = $this->record->semester_id;

        return $table
            ->query(
                Student::query()
                    ->whereHas('courseSurveys', fn (Builder $q) => $q
                        ->where('course_id', $courseId)
                        ->where('semester_id', $semesterId)
                    )
                    ->with([
                        'user:id,name',
                        'courseSurveys' => fn ($q) => $q
                            ->where('course_id', $courseId)
                            ->where('semester_id', $semesterId),
                        'enrollments' => fn ($q) => $q
                            ->where('course_offering_id', $offeringId),
                    ])
            )
            ->columns([
                TextColumn::make('enrollment_no')
                    ->label('رقم القيد')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('priority')
                    ->label('الأولوية')
                    ->getStateUsing(fn (Student $record): int => $record->courseSurveys->first()?->priority ?? 0)
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => "الأولوية {$state}"),

                TextColumn::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->getStateUsing(fn (Student $record): string => $record->enrollments->isNotEmpty() ? 'enrolled' : 'not_enrolled')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enrolled' => '✓ مسجّل',
                        'not_enrolled' => 'لم يُسجَّل بعد',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'enrolled' => 'success',
                        'not_enrolled' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->bulkActions([
                BulkAction::make('enroll_selected')
                    ->label('تسجيل المحددين')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($offeringId) {
                        $count = 0;
                        foreach ($records as $student) {
                            $created = StudentEnrollment::firstOrCreate(
                                [
                                    'student_id' => $student->id,
                                    'course_offering_id' => $offeringId,
                                ],
                                ['status' => EnrollmentStatus::Enrolled]
                            );
                            if ($created->wasRecentlyCreated) {
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("تم تسجيل {$count} طالب جديد")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('unenroll_selected')
                    ->label('إلغاء تسجيل المحددين')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($offeringId) {
                        $count = StudentEnrollment::whereIn('student_id', $records->pluck('id'))
                            ->where('course_offering_id', $offeringId)
                            ->delete();
                        Notification::make()
                            ->title("تم إلغاء تسجيل {$count} طالب")
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('(SELECT priority FROM course_surveys WHERE student_id = students.id AND course_id = ? AND semester_id = ? LIMIT 1) ASC', [
                    $courseId,
                    $semesterId,
                ])
            );
    }

    private function enrollAll(): int
    {
        $courseId = $this->record->course_id;
        $semesterId = $this->record->semester_id;
        $offeringId = $this->record->id;

        $students = Student::whereHas('courseSurveys', fn (Builder $q) => $q
            ->where('course_id', $courseId)
            ->where('semester_id', $semesterId)
        )->get();

        $count = 0;
        foreach ($students as $student) {
            $enrollment = StudentEnrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'course_offering_id' => $offeringId,
                ],
                ['status' => EnrollmentStatus::Enrolled]
            );
            if ($enrollment->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }
}
