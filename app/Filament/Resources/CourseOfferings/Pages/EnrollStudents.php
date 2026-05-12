<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\StudentStatus;
use App\Exceptions\CourseOfferingClosedException;
use App\Exceptions\CourseOutsideSpecializationException;
use App\Exceptions\EnrollmentCapReachedException;
use App\Exceptions\StudentNotActiveException;
use App\Exceptions\StudentSuspendedInSemesterException;
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

    /**
     * True when the parent semester is archived. Used to hide every write
     * action on this page so the offering becomes a read-only snapshot of
     * who was enrolled and what grades they got. Printing stays available.
     */
    private function isArchived(): bool
    {
        return (bool) $this->record->semester?->isArchived();
    }

    public function getTitle(): string
    {
        return 'طلاب المقرر — '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return 'الطلاب';
    }

    /**
     * Count of surveys that still need a decision (not yet processed).
     * A survey becomes processed when the admin either enrolls or rejects
     * the student for this course — see EnrollStudentInOffering.
     *
     * Also excludes students already actively enrolled in this offering
     * (edge case: enrolled via a non-survey path, survey still is_processed=false).
     */
    private function getSurveyCount(): int
    {
        $offeringId = $this->record->id;

        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->where('is_processed', false)
            ->where('is_retake', false)
            ->whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active))
            ->whereDoesntHave('student.activeEnrollments', fn ($q) => $q->where('course_offering_id', $offeringId))
            ->count();
    }

    /**
     * Survey entries still awaiting a decision (excludes retake requests).
     *
     * @return array<int, string>
     */
    private function getSurveyStudentOptions(): array
    {
        $offeringId = $this->record->id;

        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->where('is_processed', false)
            ->where('is_retake', false)
            ->whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active))
            ->whereDoesntHave('student.activeEnrollments', fn ($q) => $q->where('course_offering_id', $offeringId))
            ->with('student.user')
            ->orderBy('priority')
            ->get()
            ->mapWithKeys(fn (CourseSurvey $survey): array => [
                $survey->student_id => $survey->student->enrollment_no.' — '.$survey->student->user->name.' (أولوية '.$survey->priority.')',
            ])
            ->toArray();
    }

    private function getRetakeRequestCount(): int
    {
        $offeringId = $this->record->id;

        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->where('is_processed', false)
            ->where('is_retake', true)
            ->whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active))
            ->whereDoesntHave('student.activeEnrollments', fn ($q) => $q->where('course_offering_id', $offeringId))
            ->count();
    }

    /** @return array<int, string> */
    private function getRetakeStudentOptions(): array
    {
        $offeringId = $this->record->id;

        return CourseSurvey::where('course_id', $this->record->course_id)
            ->where('semester_id', $this->record->semester_id)
            ->where('is_processed', false)
            ->where('is_retake', true)
            ->whereHas('student', fn ($q) => $q->where('status', StudentStatus::Active))
            ->whereDoesntHave('student.activeEnrollments', fn ($q) => $q->where('course_offering_id', $offeringId))
            ->with('student.user')
            ->get()
            ->mapWithKeys(fn (CourseSurvey $survey): array => [
                $survey->student_id => $survey->student->enrollment_no.' — '.$survey->student->user->name,
            ])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_open')
                ->label(fn (): string => $this->record->is_open ? 'إغلاق المقرر' : 'فتح المقرر')
                ->icon(fn (): string => $this->record->is_open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color(fn (): string => $this->record->is_open ? 'danger' : 'success')
                ->visible(fn (): bool => ! $this->isArchived())
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->is_open ? 'إغلاق المقرر' : 'فتح المقرر')
                ->modalDescription(function (): string {
                    if (! $this->record->is_open) {
                        return 'سيتم فتح المقرر للتسجيل.';
                    }

                    $count = $this->record->activeEnrollments()->count();

                    if ($count > 0) {
                        return "هذه المادة فيها {$count} طالب مسجل. سيتم إلغاء تسجيلهم جميعاً عند الإغلاق. هل أنت متأكد؟";
                    }

                    return 'هل أنت متأكد من إغلاق هذا المقرر؟';
                })
                ->action(function () {
                    $this->record->update(['is_open' => ! $this->record->is_open]);

                    $message = $this->record->is_open ? 'تم فتح المقرر' : 'تم إغلاق المقرر';
                    Notification::make()->title($message)->success()->send();

                    if (! $this->record->is_open) {
                        $this->redirect(CourseOfferingResource::getUrl('index'));
                    }
                }),

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
                ->visible(fn (): bool => (bool) $this->record->is_open && ! $this->isArchived())
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
                    $capped = 0;
                    $outOfSpec = 0;
                    $suspended = 0;
                    $notActive = 0;

                    foreach ($data['student_ids'] as $studentId) {
                        $student = Student::find($studentId);

                        if (! $student) {
                            continue;
                        }

                        try {
                            $enrollment = $enroll($student, $this->record);
                        } catch (CourseOfferingClosedException $e) {
                            // Unreachable with the ->visible() guard, but defensive.
                            Notification::make()
                                ->title('المقرر مغلق للتسجيل')
                                ->body('لا يمكن تسجيل الطلاب في هذه المادة حتى يتم فتحها.')
                                ->danger()
                                ->send();

                            return;
                        } catch (StudentNotActiveException $e) {
                            $notActive++;

                            continue;
                        } catch (StudentSuspendedInSemesterException $e) {
                            $suspended++;

                            continue;
                        } catch (CourseOutsideSpecializationException $e) {
                            $outOfSpec++;

                            continue;
                        } catch (EnrollmentCapReachedException $e) {
                            $capped++;

                            continue;
                        }

                        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                            $count++;
                        }
                    }

                    if ($capped > 0 || $outOfSpec > 0 || $suspended > 0 || $notActive > 0) {
                        $bodyParts = [];
                        if ($capped > 0) {
                            $bodyParts[] = "تخطى {$capped} طالب الحد الأقصى (".EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER.' مقررات).';
                        }
                        if ($outOfSpec > 0) {
                            $bodyParts[] = "{$outOfSpec} طالب من تخصص مختلف — لا يمكن تسجيلهم في هذه المادة.";
                        }
                        if ($suspended > 0) {
                            $bodyParts[] = "{$suspended} طالب قيده موقوف لهذا الفصل.";
                        }
                        if ($notActive > 0) {
                            $bodyParts[] = "{$notActive} طالب حسابه غير نشط — لا يمكن تسجيلهم.";
                        }

                        Notification::make()
                            ->title("تم تسجيل {$count} طالب")
                            ->body(implode(' ', $bodyParts))
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title("تم تسجيل {$count} طالب")
                        ->success()
                        ->send();
                }),

            Action::make('retake_requests')
                ->label(fn (): string => 'طلبات إعادة ('.$this->getRetakeRequestCount().')')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (): bool => $this->record->is_open && $this->getRetakeRequestCount() > 0 && ! $this->isArchived())
                ->modalHeading('طلبات إعادة المادة')
                ->modalDescription('هؤلاء الطلاب سبق أن اجتازوا المادة ويرغبون في إعادتها لتحسين تقديرهم. الموافقة ستسجلهم مباشرة.')
                ->form(fn (): array => [
                    CheckboxList::make('student_ids')
                        ->label('اختر الطلاب للموافقة على إعادتهم')
                        ->options($this->getRetakeStudentOptions())
                        ->columns(1)
                        ->searchable()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalSubmitActionLabel('موافقة وتسجيل')
                ->action(function (array $data, EnrollStudentInOffering $enroll) {
                    $count = 0;

                    foreach ($data['student_ids'] as $studentId) {
                        $student = Student::find($studentId);

                        if (! $student) {
                            continue;
                        }

                        try {
                            $enroll($student, $this->record);
                            $count++;
                        } catch (\Throwable) {
                            continue;
                        }
                    }

                    Notification::make()
                        ->title("تمت الموافقة على إعادة {$count} طالب")
                        ->success()
                        ->send();
                }),

            Action::make('enroll_manual')
                ->label('إضافة طالب')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (): bool => (bool) $this->record->is_open && ! $this->isArchived())
                ->slideOver()
                ->form([
                    Select::make('student_ids')
                        ->label('اختر الطلاب')
                        ->helperText('يتم عرض الطلاب من تخصصات هذه المادة فقط.')
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            // Scope the search to students whose specialization
                            // matches one of the offering's course specializations,
                            // AND who are not already actively enrolled in this
                            // offering (no point showing students you can't add).
                            // Only Active students are shown — Inactive / Withdrawn /
                            // Graduated cannot be enrolled.
                            $specIds = $this->record->course->specializations()->pluck('specializations.id');
                            $offeringId = $this->record->id;

                            return Student::with('user')
                                ->where('status', StudentStatus::Active)
                                ->whereIn('specialization_id', $specIds)
                                ->whereDoesntHave('activeEnrollments', fn (Builder $q) => $q->where('course_offering_id', $offeringId))
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
                    $capped = 0;
                    $outOfSpec = 0;
                    $suspended = 0;
                    $notActive = 0;

                    foreach ($data['student_ids'] as $studentId) {
                        $student = Student::find($studentId);

                        if (! $student) {
                            continue;
                        }

                        try {
                            $enrollment = $enroll($student, $this->record);
                        } catch (CourseOfferingClosedException $e) {
                            // Unreachable with the ->visible() guard, but defensive.
                            Notification::make()
                                ->title('المقرر مغلق للتسجيل')
                                ->body('لا يمكن تسجيل الطلاب في هذه المادة حتى يتم فتحها.')
                                ->danger()
                                ->send();

                            return;
                        } catch (StudentNotActiveException $e) {
                            $notActive++;

                            continue;
                        } catch (StudentSuspendedInSemesterException $e) {
                            $suspended++;

                            continue;
                        } catch (CourseOutsideSpecializationException $e) {
                            $outOfSpec++;

                            continue;
                        } catch (EnrollmentCapReachedException $e) {
                            $capped++;

                            continue;
                        }

                        if ($enrollment->wasRecentlyCreated || $enrollment->wasChanged('status')) {
                            $count++;
                        }
                    }

                    if ($capped > 0 || $outOfSpec > 0 || $suspended > 0 || $notActive > 0) {
                        $bodyParts = [];
                        if ($capped > 0) {
                            $bodyParts[] = "تخطى {$capped} طالب الحد الأقصى (".EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER.' مقررات).';
                        }
                        if ($outOfSpec > 0) {
                            $bodyParts[] = "{$outOfSpec} طالب من تخصص مختلف.";
                        }
                        if ($suspended > 0) {
                            $bodyParts[] = "{$suspended} طالب قيده موقوف لهذا الفصل.";
                        }
                        if ($notActive > 0) {
                            $bodyParts[] = "{$notActive} طالب حسابه غير نشط — لا يمكن تسجيلهم.";
                        }

                        Notification::make()
                            ->title("تم إضافة {$count} طالب")
                            ->body(implode(' ', $bodyParts))
                            ->warning()
                            ->send();

                        return;
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
                    ->with([
                        'user:id,name',
                        'activeEnrollments' => fn ($q) => $q->where('course_offering_id', $offeringId)->with('grade'),
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

                TextColumn::make('coursework_grade')
                    ->label('الأعمال')
                    ->placeholder('—')
                    ->getStateUsing(fn (Student $record): ?string => $record->activeEnrollments->first()?->grade?->coursework_grade),

                TextColumn::make('final_grade')
                    ->label('النهائي')
                    ->placeholder('—')
                    ->getStateUsing(fn (Student $record): ?string => $record->activeEnrollments->first()?->grade?->final_grade),

                TextColumn::make('total')
                    ->label('المجموع')
                    ->placeholder('—')
                    ->getStateUsing(fn (Student $record): ?string => $record->activeEnrollments->first()?->grade?->total),

                TextColumn::make('letter')
                    ->label('التقدير')
                    ->placeholder('—')
                    ->badge()
                    ->getStateUsing(fn (Student $record): ?string => $record->activeEnrollments->first()?->grade?->letter)
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B+', 'B' => 'info',
                        'C+', 'C' => 'warning',
                        'D+', 'D' => 'gray',
                        'F' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('unenroll')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => ! $this->isArchived())
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
                    ->visible(fn (): bool => ! $this->isArchived())
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
