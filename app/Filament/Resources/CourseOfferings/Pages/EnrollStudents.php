<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
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
        return 'طلاب المقرر — '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return 'الطلاب';
    }

    private function getSurveyCount(): int
    {
        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->count();
    }

    private function getSurveyStudentOptions(): array
    {
        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->with('student.user')
            ->orderBy('priority')
            ->get()
            ->mapWithKeys(fn (CourseSurvey $survey): array => [
                $survey->student_id => $survey->student->enrollment_no.' — '.$survey->student->user->name.' (أولوية '.$survey->priority.')',
            ])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_students')
                ->label('طباعة كشف الطلاب')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('course-offering.print-students', $this->record))
                ->openUrlInNewTab(),

            Action::make('survey_students')
                ->label(fn (): string => 'طلبوا المادة ('.$this->getSurveyCount().')')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->modalDescription(fn (): ?string => $this->getSurveyCount() === 0
                    ? 'لم يتقدم أي طالب لطلب هذه المادة في الفصل الحالي.'
                    : null)
                ->form(fn (): array => [
                    CheckboxList::make('student_ids')
                        ->label('اختر الطلاب للتسجيل')
                        ->options($this->getSurveyStudentOptions())
                        ->columns(1)
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data, EnrollStudentInOffering $enroll) {
                    $count = 0;

                    foreach ($data['student_ids'] as $studentId) {
                        $student = Student::find($studentId);

                        if (! $student) {
                            continue;
                        }

                        $enrollment = $enroll($student, $this->record);

                        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                            $count++;
                        }
                    }

                    Notification::make()
                        ->title("تم تسجيل {$count} طالب")
                        ->success()
                        ->send();
                }),

            Action::make('enroll_manual')
                ->label('إضافة طالب')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->slideOver()
                ->form([
                    Select::make('student_ids')
                        ->label('اختر الطلاب')
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return Student::with('user')
                                ->where(function (Builder $q) use ($search): void {
                                    $q->whereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%"))
                                        ->orWhere('enrollment_no', 'like', "%{$search}%");
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Student $s): array => [
                                    $s->id => $s->enrollment_no.' — '.$s->user->name,
                                ])
                                ->toArray();
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => Student::with('user')
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn (Student $s): array => [
                                $s->id => $s->enrollment_no.' — '.$s->user->name,
                            ])
                            ->toArray()
                        )
                        ->required(),
                ])
                ->action(function (array $data, EnrollStudentInOffering $enroll) {
                    $count = 0;

                    foreach ($data['student_ids'] as $studentId) {
                        $student = Student::find($studentId);

                        if (! $student) {
                            continue;
                        }

                        $enrollment = $enroll($student, $this->record);

                        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                            $count++;
                        }
                    }

                    Notification::make()
                        ->title("تم إضافة {$count} طالب")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $offeringId = $this->record->id;

        return $table
            ->query(
                Student::query()
                    ->whereHas('activeEnrollments', fn (Builder $q) => $q->where('course_offering_id', $offeringId))
                    ->with(['user:id,name'])
            )
            ->columns([
                TextColumn::make('enrollment_no')
                    ->label('رقم القيد')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('اسم الطالب')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('unenroll')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Student $record, DropStudentFromOffering $drop) {
                        $drop($record, $this->record);

                        Notification::make()
                            ->title('تم إلغاء تسجيل الطالب')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('unenroll_selected')
                    ->label('إلغاء تسجيل المحددين')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records, DropStudentFromOffering $drop) {
                        $count = 0;

                        foreach ($records as $student) {
                            if ($drop($student, $this->record)) {
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title("تم إلغاء تسجيل {$count} طالب")
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
