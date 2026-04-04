<?php

namespace App\Filament\Student\Pages;

use App\Models\ClearanceRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ClearancePage extends Page
{
    protected string $view = 'filament.student.pages.clearance';

    protected static ?string $navigationLabel = 'الاستماحة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 9;

    public function getClearance(): ?ClearanceRequest
    {
        $student = auth()->user()->student;

        return $student?->clearanceRequest;
    }

    public function isThesisApproved(): bool
    {
        $thesis = auth()->user()->student?->thesisRegistration;

        return $thesis?->status === 'approved';
    }

    public function requestClearance(): void
    {
        $student = auth()->user()->student;

        if (! $student) {
            Notification::make()->title('لا يوجد سجل طالب')->danger()->send();

            return;
        }

        if (! $this->isThesisApproved()) {
            Notification::make()->title('يجب قبول الرسالة أولاً قبل طلب الاستماحة')->danger()->send();

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
