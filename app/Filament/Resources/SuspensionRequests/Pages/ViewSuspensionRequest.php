<?php

namespace App\Filament\Resources\SuspensionRequests\Pages;

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Filament\Resources\SuspensionRequests\SuspensionRequestResource;
use App\Models\SuspensionRequest;
use App\Services\AuditService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSuspensionRequest extends ViewRecord
{
    protected static string $resource = SuspensionRequestResource::class;

    protected string $view = 'filament.resources.suspension-requests.pages.view-suspension-request';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return $this->record->student?->user?->name ?? (string) $this->record->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('قبول')
                ->icon(Heroicon::Check)
                ->color('success')
                ->visible(fn (): bool => $this->record->status === SuspensionStatus::Pending)
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'قبول إيقاف قيد '.($this->record->student?->user?->name ?? ''))
                ->modalDescription(fn (): string => 'سيتم سحب تسجيلات '.($this->record->student?->user?->name ?? 'الطالب').' في هذا الفصل فقط، وستبقى حالته نشطة للفصول الأخرى.')
                ->action(function (AuditService $audit, NotificationService $notifier): void {
                    /** @var SuspensionRequest $record */
                    $record = $this->record;
                    $student = $record->student;

                    $record->update([
                        'status' => SuspensionStatus::Approved,
                        'decided_by' => auth()->id(),
                        'decided_at' => now(),
                    ]);

                    $student->update(['status' => StudentStatus::OnHold]);

                    $audit->log(
                        'suspension_approved',
                        $record,
                        ['status' => SuspensionStatus::Pending->value],
                        ['status' => SuspensionStatus::Approved->value],
                    );
                    $notifier->notifySuspensionDecision($record->fresh());

                    Notification::make()
                        ->title('تم قبول طلب إيقاف القيد')
                        ->body('تم إيقاف قيد الطالب وسحب جميع تسجيلاته النشطة.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'decided_by', 'decided_at']);
                }),

            Action::make('reject')
                ->label('رفض')
                ->icon(Heroicon::XMark)
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === SuspensionStatus::Pending)
                ->form([
                    Textarea::make('decision_notes')
                        ->label('سبب الرفض')
                        ->required(),
                ])
                ->action(function (array $data, NotificationService $notifier, AuditService $audit): void {
                    /** @var SuspensionRequest $record */
                    $record = $this->record;

                    $record->update([
                        'status' => SuspensionStatus::Rejected,
                        'decided_by' => auth()->id(),
                        'decided_at' => now(),
                        'decision_notes' => $data['decision_notes'],
                    ]);

                    $audit->log('suspension_rejected', $record);
                    $notifier->notifySuspensionDecision($record->fresh());

                    Notification::make()->title('تم رفض الطلب')->success()->send();

                    $this->refreshFormData(['status', 'decided_by', 'decided_at', 'decision_notes']);
                }),
        ];
    }
}
