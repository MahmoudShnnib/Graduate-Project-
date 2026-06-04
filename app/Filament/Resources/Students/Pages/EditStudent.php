<?php

namespace App\Filament\Resources\Students\Pages;

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use App\Services\AuditService;
use App\Services\EnrollmentNumberService;
use App\Services\GradeCalculationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->changeStatusAction(),
            DeleteAction::make()
                ->modalHeading('حذف الطالب')
                ->modalDescription('سيتم حذف سجل الطالب وحساب المستخدم وطلب التسجيل المرتبط به. هل أنت متأكد؟')
                ->after(function ($record) {
                    $record->user?->delete();
                }),
        ];
    }

    /**
     * Dedicated header action for changing {@see \App\Models\Student::$status}.
     * Built as a standalone action (not wired into the form save) so it gets
     * a proper Filament modal with a Select + live consequence warning.
     * Save-button on the main form stays untouched (no confirmation on save
     * unless the status changed, which can't happen here because the status
     * Select is removed from the main form).
     */
    protected function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('تغيير الحالة')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->modalHeading('تغيير حالة الطالب')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalSubmitActionLabel('تأكيد')
            ->modalCancelActionLabel('إلغاء')
            ->fillForm(fn () => ['status' => $this->record->status?->value])
            ->form([
                Placeholder::make('current_status')
                    ->label('الحالة الحالية')
                    ->content(fn () => $this->record->getDisplayStatusLabel()),

                Select::make('status')
                    ->label('الحالة الجديدة')
                    ->options(StudentStatus::class)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),

                Placeholder::make('consequence')
                    ->label('ما الذي سيحدث؟')
                    ->content(fn (Get $get): string => $this->getStatusChangeWarning(
                        $get('status') instanceof StudentStatus
                            ? $get('status')->value
                            : $get('status')
                    )),
            ])
            ->action(function (array $data, AuditService $audit): void {
                $rawStatus = $data['status'] ?? null;
                $newStatus = $rawStatus instanceof StudentStatus
                    ? $rawStatus
                    : StudentStatus::from((string) $rawStatus);

                if ($newStatus === $this->record->status) {
                    Notification::make()->title('لم يتم تغيير الحالة')->warning()->send();

                    return;
                }

                $oldStatus = $this->record->status;
                $this->record->update(['status' => $newStatus]);
                $this->rescindActiveSemesterSuspensionIfOverridden($this->record);

                $audit->log(
                    'student_status_changed',
                    $this->record,
                    oldValues: ['status' => $oldStatus->value],
                    newValues: ['status' => $newStatus->value],
                );

                Notification::make()
                    ->title('تم تحديث حالة الطالب')
                    ->body('الحالة الجديدة: '.$newStatus->label())
                    ->success()
                    ->send();

                $this->refreshFormData(['status']);
            });
    }

    private function getStatusChangeWarning(?string $newStatusValue): string
    {
        $newStatus = $newStatusValue ? StudentStatus::tryFrom($newStatusValue) : null;

        return match ($newStatus) {
            StudentStatus::Active => 'سيتم تغيير حالة الطالب إلى "مستمر". إذا كان لديه إيقاف قيد معتمد للفصل الحالي فسيتم إلغاؤه، وسيتمكن الطالب من استخدام جميع الخدمات.',
            StudentStatus::OnHold => 'سيتم إيقاف قيد الطالب. سيتم سحب جميع تسجيلاته النشطة في المقررات. سيتمكن من الاطلاع على بياناته فقط.',
            StudentStatus::Inactive => 'سيتم تسجيل الطالب كمنقطع. سيتم سحب جميع تسجيلاته النشطة. سيتمكن من الاطلاع على بياناته فقط.',
            StudentStatus::Transferred => 'سيتم تسجيل انتقال الطالب إلى جهة أخرى. سيتم سحب جميع تسجيلاته النشطة. هذا الإجراء يؤثر على ملفه الأكاديمي.',
            StudentStatus::Graduated => 'سيتم تسجيل تخرج الطالب. سيتم سحب جميع تسجيلاته النشطة. لن يتمكن من تقديم أي طلبات جديدة.',
            StudentStatus::Dismissed => 'سيتم فصل الطالب. سيتم سحب جميع تسجيلاته النشطة. يمكنه تقديم طلب استثنائي فقط.',
            default => 'اختر الحالة الجديدة لعرض الأثر.',
        };
    }

    /**
     * Populate the hidden transfer_courses form field from existing TransferCourse
     * rows so the "معادلة مواد" modal opens with the student's current entries.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['transfer_courses'] = $this->record->transferCourses()
            ->get(['course_id', 'coursework_grade', 'final_grade'])
            ->map(fn ($tc): array => [
                'course_id' => $tc->course_id,
                'coursework_grade' => $tc->coursework_grade,
                'final_grade' => $tc->final_grade,
            ])
            ->toArray();

        return $data;
    }

    /**
     * Persist transfer_courses form data to the TransferCourse table and
     * trigger a GPA recalculation. Mirrors the create-side logic in
     * CreateStudent::handleRecordCreation.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $transferCourses = $data['transfer_courses'] ?? [];
        $isTransfer = (bool) ($data['is_transfer'] ?? false);

        $oldSpecializationId = $record->specialization_id;
        $oldEnrollmentNo = $record->enrollment_no;

        // Strip from $data — not a Student column.
        unset($data['transfer_courses']);

        $record = parent::handleRecordUpdate($record, $data);

        // Replace existing transfer courses with the new set.
        // delete() + create() is simplest for rich-data repeaters; matches the
        // create path pattern.
        $record->transferCourses()->delete();

        if ($isTransfer && ! empty($transferCourses)) {
            foreach ($transferCourses as $courseData) {
                $record->transferCourses()->create([
                    'course_id' => $courseData['course_id'],
                    'coursework_grade' => $courseData['coursework_grade'],
                    'final_grade' => $courseData['final_grade'],
                ]);
            }
        }

        // Recalculate GPA — transfer courses count toward it per
        // GradeCalculationService. Non-Active students are skipped inside the service.
        app(GradeCalculationService::class)->recalculateStudentGPA($record);

        // If the specialization changed and the student has an enrollment number,
        // re-issue a new number under the new spec. Preserves the original year
        // and session flag from the current number.
        if ($record->specialization_id !== $oldSpecializationId && $record->enrollment_no) {
            $record->refresh();
            $newEnrollmentNo = app(EnrollmentNumberService::class)
                ->regenerateForSpecializationChange($record, $record->specialization);

            Notification::make()
                ->title('تم تحديث رقم القيد')
                ->body("الرقم القديم: {$oldEnrollmentNo} ← الرقم الجديد: {$newEnrollmentNo}")
                ->success()
                ->send();
        }

        // Admin-override: if the saved status is Active and there's still an
        // approved semester-scoped suspension for the active semester, the
        // admin's explicit Save means "this student should be active now".
        // Rescind the suspension (flip to Rejected with an explanatory note)
        // so canPerformActions / display helpers / portal gates all agree
        // with the admin's intent immediately.
        $this->rescindActiveSemesterSuspensionIfOverridden($record);

        return $record;
    }

    private function rescindActiveSemesterSuspensionIfOverridden(Model $record): void
    {
        if ($record->status !== StudentStatus::Active) {
            return;
        }

        $activeSemester = Semester::active();

        if (! $activeSemester) {
            return;
        }

        $affected = SuspensionRequest::where('student_id', $record->id)
            ->where('semester_id', $activeSemester->id)
            ->where('status', SuspensionStatus::Approved)
            ->update([
                'status' => SuspensionStatus::Rejected,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
                'decision_notes' => 'تم إلغاء إيقاف القيد من قبل الإدارة عبر تحرير الطالب.',
            ]);

        if ($affected > 0) {
            Notification::make()
                ->title('تم إلغاء إيقاف القيد لهذا الفصل')
                ->body('تم إعادة الطالب إلى الحالة النشطة. لإعادة تسجيله في المقررات، يرجى استخدام شاشة إدارة المقرر.')
                ->success()
                ->send();
        }
    }
}
