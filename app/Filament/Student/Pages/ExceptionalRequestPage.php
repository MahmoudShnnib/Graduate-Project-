<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Models\ExceptionalRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExceptionalRequestPage extends Page
{
    protected string $view = 'filament.student.pages.exceptional-request';

    protected static ?string $navigationLabel = 'طلب استثنائي';

    protected static ?string $title = 'طلب استثنائي';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?int $navigationSort = 9;

    public string $reason = '';

    public static function shouldRegisterNavigation(): bool
    {
        $student = auth()->user()?->student;

        return $student && $student->status === StudentStatus::Dismissed;
    }

    public static function canAccess(): bool
    {
        $student = auth()->user()?->student;

        return $student && $student->status === StudentStatus::Dismissed;
    }

    public function getStudent(): ?\App\Models\Student
    {
        return auth()->user()?->student;
    }

    public function getLatestRequest(): ?ExceptionalRequest
    {
        $student = $this->getStudent();

        if (! $student) {
            return null;
        }

        return $student->exceptionalRequests()->latest()->first();
    }

    public function canSubmitNewRequest(): bool
    {
        $student = $this->getStudent();

        if (! $student || $student->status !== StudentStatus::Dismissed) {
            return false;
        }

        $latest = $this->getLatestRequest();

        if (! $latest) {
            return true;
        }

        if (! $latest->can_reapply) {
            return false;
        }

        return in_array($latest->status, [
            ExceptionalRequestStatus::Rejected,
        ]);
    }

    public function hasPendingRequest(): bool
    {
        $latest = $this->getLatestRequest();

        return $latest && in_array($latest->status->value, ['pending', 'summoned']);
    }

    public function isBannedFromReapply(): bool
    {
        $latest = $this->getLatestRequest();

        return $latest && ! $latest->can_reapply;
    }

    public function submit(): void
    {
        $student = $this->getStudent();

        if (! $student || ! $this->canSubmitNewRequest()) {
            Notification::make()->title('لا يمكنك تقديم طلب جديد حالياً')->danger()->send();

            return;
        }

        $this->validate([
            'reason' => ['required', 'string', 'min:10'],
        ], [
            'reason.required' => 'يرجى كتابة سبب الطلب.',
            'reason.min' => 'يرجى كتابة سبب مفصّل (10 أحرف على الأقل).',
        ]);

        ExceptionalRequest::create([
            'student_id' => $student->id,
            'reason' => $this->reason,
        ]);

        $this->reason = '';

        Notification::make()
            ->title('تم تقديم الطلب الاستثنائي بنجاح')
            ->body('سيتم مراجعة طلبك من قبل الإدارة.')
            ->success()
            ->send();
    }
}
