<?php

namespace App\Filament\Student\Pages;

use App\Enums\DiscussionStatus;
use App\Models\ClearanceRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ClearancePage extends Page
{
    protected string $view = 'filament.student.pages.clearance';

    protected static ?string $navigationLabel = 'الاستماحة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?int $navigationSort = 9;

    public function getClearance(): ?ClearanceRequest
    {
        $student = auth()->user()->student;

        return $student?->clearanceRequest;
    }

    public function isDiscussionCompleted(): bool
    {
        $student = auth()->user()->student;

        if (! $student) {
            return false;
        }

        return $student->discussionRequests()
            ->where('status', DiscussionStatus::Completed)
            ->exists();
    }

    public function requestClearance(): void
    {
        $student = auth()->user()->student;

        if (! $student) {
            Notification::make()->title('لا يوجد سجل طالب')->danger()->send();

            return;
        }

        if (! $student->canPerformActions()) {
            Notification::make()
                ->title('لا يمكنك تقديم هذا الطلب')
                ->body('حسابك ليس في حالة نشطة حالياً.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->isDiscussionCompleted()) {
            Notification::make()->title('يجب إتمام المناقشة والتجليد والتسليم قبل طلب الاستماحة')->danger()->send();

            return;
        }

        if ($student->clearanceRequest) {
            Notification::make()->title('لقد أرسلت طلب استماحة من قبل')->warning()->send();

            return;
        }

        ClearanceRequest::create([
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        Notification::make()->title('تم إرسال طلب الاستماحة للمراجعة')->success()->send();
    }
}
