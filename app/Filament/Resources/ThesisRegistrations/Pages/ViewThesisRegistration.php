<?php

namespace App\Filament\Resources\ThesisRegistrations\Pages;

use App\Enums\ThesisStatus;
use App\Filament\Resources\ThesisRegistrations\ThesisRegistrationResource;
use App\Models\ThesisRegistration;
use App\Services\AuditService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewThesisRegistration extends ViewRecord
{
    protected static string $resource = ThesisRegistrationResource::class;

    protected string $view = 'filament.resources.thesis-registrations.pages.view-thesis-registration';

    public function getTitle(): string
    {
        return 'طلب تسجيل رسالة — '.$this->record->student->user->name;
    }

    protected function resolveRecord(int|string $key): ThesisRegistration
    {
        return ThesisRegistration::with([
            'student.user',
            'student.specialization',
            'supervisor',
            'facultySupervisor',
            'decidedBy',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->printForMinistryAction(),
            $this->rejectAction(),
            $this->approveAction(),
        ];
    }

    private function printForMinistryAction(): Action
    {
        return Action::make('print_for_ministry')
            ->label('طباعة للوزارة')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('warning')
            ->visible(fn () => $this->record->status === ThesisStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading('طباعة للوزارة')
            ->modalDescription('سيتم تغيير حالة الطلب إلى "في انتظار الوزارة". هل تريد المتابعة؟')
            ->modalSubmitActionLabel('تأكيد الطباعة')
            ->action(function () {
                $this->record->update([
                    'status' => ThesisStatus::Printed,
                    'printed_at' => now(),
                ]);

                Notification::make()
                    ->title('تم تسجيل الطباعة')
                    ->body('حالة الطلب الآن: في انتظار الوزارة')
                    ->success()
                    ->send();
            });
    }

    private function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon(Heroicon::XMark)
            ->color('danger')
            ->visible(fn () => $this->record->status === ThesisStatus::Printed)
            ->requiresConfirmation()
            ->modalHeading('رفض طلب تسجيل الرسالة')
            ->modalDescription('يرجى كتابة سبب الرفض. سيظهر للطالب ويمكنه إعادة التقديم.')
            ->modalSubmitActionLabel('تأكيد الرفض')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, NotificationService $notifier) {
                $this->record->update([
                    'status' => ThesisStatus::Rejected,
                    'rejection_reason' => $data['rejection_reason'],
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $notifier->notifyThesisDecision($this->record->fresh());

                Notification::make()
                    ->title('تم رفض الطلب')
                    ->body('سيظهر سبب الرفض للطالب.')
                    ->success()
                    ->send();
            });
    }

    private function approveAction(): Action
    {
        return Action::make('approve')
            ->label('قبول')
            ->icon(Heroicon::Check)
            ->color('success')
            ->visible(fn () => $this->record->status === ThesisStatus::Printed)
            ->requiresConfirmation()
            ->modalHeading('قبول طلب تسجيل الرسالة')
            ->modalDescription('سيتم قبول تسجيل الرسالة لهذا الطالب. هل أنت متأكد؟')
            ->modalSubmitActionLabel('تأكيد القبول')
            ->action(function (AuditService $audit, NotificationService $notifier) {
                $student = $this->record->student;

                if (! $student->isEligibleForThesis()) {
                    Notification::make()
                        ->title('الطالب غير مؤهل')
                        ->body('يجب إتمام جميع المقررات العامة والتخصصية ومقررتين اختياريتين بمعدل 3.0 أو أعلى')
                        ->danger()
                        ->send();

                    return;
                }

                $this->record->update([
                    'status' => ThesisStatus::Approved,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $audit->log('thesis_approved', $this->record, ['status' => 'printed'], ['status' => 'approved']);
                $notifier->notifyThesisDecision($this->record->fresh());

                Notification::make()
                    ->title('تم قبول تسجيل الرسالة')
                    ->success()
                    ->send();
            });
    }

    public function getCoursesCompletionDate(): ?string
    {
        $student = $this->record->student;

        $lastSemester = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->where('status', 'approved'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
            ->orderByDesc('semesters.end_date')
            ->select('semesters.end_date')
            ->first();

        return $lastSemester?->end_date;
    }

    public function getSupervisorDetails(): ?array
    {
        $reg = $this->record;

        if ($reg->is_external_supervisor && $reg->supervisor) {
            return [
                'type' => 'خارجي',
                'name' => $reg->supervisor->name,
                'academic_rank' => $reg->supervisor->academic_rank,
                'qualification' => $reg->supervisor->qualification,
                'institution' => $reg->supervisor->institution,
                'email' => $reg->supervisor->email,
                'phone' => $reg->supervisor->phone,
            ];
        }

        if (! $reg->is_external_supervisor && $reg->facultySupervisor) {
            return [
                'type' => 'من الجامعة',
                'name' => $reg->facultySupervisor->name,
                'email' => $reg->facultySupervisor->email,
                'phone' => $reg->facultySupervisor->phone,
            ];
        }

        if ($reg->supervisor_name) {
            return [
                'type' => '—',
                'name' => $reg->supervisor_name,
                'academic_rank' => $reg->supervisor_degree,
                'institution' => $reg->supervisor_employer,
            ];
        }

        return null;
    }
}
