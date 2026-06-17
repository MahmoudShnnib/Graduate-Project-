<?php

namespace App\Filament\Resources\DiscussionRequests\Pages;

use App\Enums\DiscussionStatus;
use App\Filament\Resources\DiscussionRequests\DiscussionRequestResource;
use App\Models\DiscussionRequest;
use App\Services\AuditService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewDiscussionRequest extends ViewRecord
{
    protected static string $resource = DiscussionRequestResource::class;

    protected string $view = 'filament.resources.discussion-requests.pages.view-discussion-request';

    public function getTitle(): string
    {
        return 'طلب مناقشة — '.$this->record->student->user->name;
    }

    protected function resolveRecord(int|string $key): DiscussionRequest
    {
        return DiscussionRequest::with([
            'student.user',
            'student.specialization',
            'thesisRegistration.supervisor',
            'thesisRegistration.facultySupervisor',
            'decidedBy',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Phase 1: Pending → Reject
            $this->phase1RejectAction(),
            // Phase 1: Pending → Accept (with date)
            $this->phase1AcceptAction(),
            // Phase 2: Scheduled → Discussion Done
            $this->phase2DiscussionDoneAction(),
            // Phase 3: Discussed → Reject
            $this->phase3RejectAction(),
            // Phase 3: Discussed → Accept
            $this->phase3AcceptAction(),
            // Phase 3: Discussed → Accept with Edits
            $this->phase3AcceptWithEditsAction(),
            // Phase 3→4: AcceptedWithEdits → Edits Done
            $this->editsDoneAction(),
            // Phase 4: DiscussionApproved → Binding & Submission Done
            $this->phase4CompletedAction(),
        ];
    }

    private function phase1RejectAction(): Action
    {
        return Action::make('phase1_reject')
            ->label('رفض')
            ->icon(Heroicon::XMark)
            ->color('danger')
            ->visible(fn () => $this->record->status === DiscussionStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading('رفض طلب المناقشة')
            ->modalDescription('يرجى كتابة سبب الرفض. سيظهر للطالب ويمكنه إعادة التقديم.')
            ->modalSubmitActionLabel('تأكيد الرفض')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::Rejected,
                    'rejection_reason' => $data['rejection_reason'],
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $audit->log('discussion_rejected', $this->record);

                Notification::make()
                    ->title('تم رفض طلب المناقشة')
                    ->success()
                    ->send();

                $this->dispatch('refresh-sidebar');
            });
    }

    private function phase1AcceptAction(): Action
    {
        return Action::make('phase1_accept')
            ->label('قبول')
            ->icon(Heroicon::Check)
            ->color('success')
            ->visible(fn () => $this->record->status === DiscussionStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading('قبول طلب المناقشة')
            ->modalDescription('يرجى تحديد موعد المناقشة.')
            ->modalSubmitActionLabel('تأكيد القبول')
            ->form([
                DatePicker::make('discussion_date')
                    ->label('موعد المناقشة')
                    ->required()
                    ->native(false)
                    ->minDate(now()),
            ])
            ->action(function (array $data, AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::Scheduled,
                    'discussion_date' => $data['discussion_date'],
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $audit->log('discussion_scheduled', $this->record);

                Notification::make()
                    ->title('تم قبول الطلب وتحديد موعد المناقشة')
                    ->body('موعد المناقشة: '.$this->record->fresh()->discussion_date->format('Y/m/d'))
                    ->success()
                    ->send();

                $this->dispatch('refresh-sidebar');
            });
    }

    private function phase2DiscussionDoneAction(): Action
    {
        return Action::make('discussion_done')
            ->label('تمت المناقشة')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('info')
            ->visible(fn () => $this->record->status === DiscussionStatus::Scheduled)
            ->requiresConfirmation()
            ->modalHeading('تأكيد إتمام المناقشة')
            ->modalDescription('هل تمت مناقشة الرسالة بنجاح؟ سيتم الانتقال لتحديد نتيجة المناقشة.')
            ->modalSubmitActionLabel('تأكيد')
            ->action(function (AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::Discussed,
                    'discussed_at' => now(),
                ]);

                $audit->log('discussion_done', $this->record);

                Notification::make()
                    ->title('تم تسجيل إتمام المناقشة')
                    ->body('يمكنك الآن تحديد نتيجة المناقشة.')
                    ->success()
                    ->send();
            });
    }

    private function phase3RejectAction(): Action
    {
        return Action::make('phase3_reject')
            ->label('رفض')
            ->icon(Heroicon::XMark)
            ->color('danger')
            ->visible(fn () => $this->record->status === DiscussionStatus::Discussed)
            ->requiresConfirmation()
            ->modalHeading('رفض نتيجة المناقشة')
            ->modalDescription('سيتم رفض الطلب ويمكن للطالب إعادة التقديم من البداية.')
            ->modalSubmitActionLabel('تأكيد الرفض')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::DiscussionRejected,
                    'rejection_reason' => $data['rejection_reason'],
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $audit->log('discussion_rejected_post', $this->record);

                Notification::make()
                    ->title('تم رفض نتيجة المناقشة')
                    ->success()
                    ->send();
            });
    }

    private function phase3AcceptAction(): Action
    {
        return Action::make('phase3_accept')
            ->label('قبول')
            ->icon(Heroicon::Check)
            ->color('success')
            ->visible(fn () => $this->record->status === DiscussionStatus::Discussed)
            ->requiresConfirmation()
            ->modalHeading('قبول نتيجة المناقشة')
            ->modalDescription('سيتم قبول نتيجة المناقشة والانتقال لمرحلة التجليد والتسليم.')
            ->modalSubmitActionLabel('تأكيد القبول')
            ->action(function (AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::DiscussionApproved,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                $audit->log('discussion_approved', $this->record);

                Notification::make()
                    ->title('تمت الموافقة على نتيجة المناقشة')
                    ->success()
                    ->send();
            });
    }

    private function phase3AcceptWithEditsAction(): Action
    {
        return Action::make('accept_with_edits')
            ->label('قبول مع تعديلات')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->visible(fn () => $this->record->status === DiscussionStatus::Discussed)
            ->requiresConfirmation()
            ->modalHeading('قبول مع تعديلات')
            ->modalDescription('سيتم قبول المناقشة مع طلب تعديلات. سيظهر زر "تم التعديلات" بعد ذلك.')
            ->modalSubmitActionLabel('تأكيد')
            ->action(function () {
                $this->record->update([
                    'status' => DiscussionStatus::AcceptedWithEdits,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                Notification::make()
                    ->title('تم تسجيل القبول مع تعديلات')
                    ->body('سيظهر زر "تم التعديلات" عند إتمام الطالب للتعديلات.')
                    ->success()
                    ->send();
            });
    }

    private function editsDoneAction(): Action
    {
        return Action::make('edits_done')
            ->label('تم التعديلات')
            ->icon(Heroicon::Check)
            ->color('success')
            ->visible(fn () => $this->record->status === DiscussionStatus::AcceptedWithEdits)
            ->requiresConfirmation()
            ->modalHeading('تأكيد إتمام التعديلات')
            ->modalDescription('هل تم إتمام التعديلات المطلوبة؟ سيتم الانتقال لمرحلة التجليد والتسليم.')
            ->modalSubmitActionLabel('تأكيد')
            ->action(function () {
                $this->record->update([
                    'status' => DiscussionStatus::DiscussionApproved,
                    'decided_by' => auth()->id(),
                    'decided_at' => now(),
                ]);

                Notification::make()
                    ->title('تم تأكيد إتمام التعديلات')
                    ->success()
                    ->send();
            });
    }

    private function phase4CompletedAction(): Action
    {
        return Action::make('binding_done')
            ->label('تم التجليد والتسليم')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn () => $this->record->status === DiscussionStatus::DiscussionApproved)
            ->requiresConfirmation()
            ->modalHeading('تأكيد التجليد والتسليم')
            ->modalDescription('هل تم التجليد والتسليم؟ سيتمكن الطالب بعد ذلك من تقديم طلب الاستماحة.')
            ->modalSubmitActionLabel('تأكيد')
            ->action(function (AuditService $audit) {
                $this->record->update([
                    'status' => DiscussionStatus::Completed,
                    'completed_at' => now(),
                ]);

                $audit->log('discussion_completed', $this->record);

                Notification::make()
                    ->title('تم التجليد والتسليم')
                    ->body('يمكن للطالب الآن تقديم طلب الاستماحة.')
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
        $reg = $this->record->thesisRegistration;

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
