<?php

namespace App\Filament\Student\Pages;

use App\Enums\SuspensionStatus;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use App\Services\AuditService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SuspensionRequestPage extends Page
{
    protected string $view = 'filament.student.pages.suspension-request';

    protected static ?string $navigationLabel = 'طلبات إيقاف قيد';

    protected static ?string $title = 'طلبات إيقاف قيد';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pause-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?int $navigationSort = 7;

    public string $reason = '';

    public ?int $semesterId = null;

    public function mount(): void
    {
        $activeSemester = Semester::where('is_active', true)->first();
        if ($activeSemester) {
            $this->semesterId = $activeSemester->id;
        }
    }

    public function getSemesters(): Collection
    {
        return Semester::orderByDesc('year')->get();
    }

    public function getHistory(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return SuspensionRequest::where('student_id', $student->id)
            ->with('semester')
            ->latest()
            ->get();
    }

    public function hasPendingRequest(): bool
    {
        $student = auth()->user()->student;

        if (! $student) {
            return false;
        }

        return SuspensionRequest::where('student_id', $student->id)
            ->where('status', SuspensionStatus::Pending)
            ->exists();
    }

    public function hasAnyApprovedRequest(): bool
    {
        $student = auth()->user()->student;

        if (! $student) {
            return false;
        }

        return SuspensionRequest::where('student_id', $student->id)
            ->where('status', SuspensionStatus::Approved)
            ->exists();
    }

    /**
     * @return array<int>
     */
    public function getBlockedSemesterIds(): array
    {
        $student = auth()->user()->student;

        if (! $student) {
            return [];
        }

        return SuspensionRequest::where('student_id', $student->id)
            ->whereIn('status', [SuspensionStatus::Approved, SuspensionStatus::Rejected])
            ->pluck('semester_id')
            ->all();
    }

    public function isSelectedSemesterBlocked(): bool
    {
        if (! $this->semesterId) {
            return false;
        }

        return in_array((int) $this->semesterId, $this->getBlockedSemesterIds(), true);
    }

    public function submit(): void
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

        if ($this->hasAnyApprovedRequest()) {
            Notification::make()
                ->title('لا يمكنك تقديم طلب انقطاع جديد')
                ->body('تمت الموافقة على طلب انقطاع سابق. لا يُسمح بتقديم طلب جديد.')
                ->danger()
                ->send();

            return;
        }

        if ($this->hasPendingRequest()) {
            Notification::make()->title('لديك طلب انقطاع قيد المراجعة بالفعل')->warning()->send();

            return;
        }

        $this->validate([
            'reason' => 'required|min:10',
            'semesterId' => 'required|exists:semesters,id',
        ], [
            'reason.required' => 'يرجى كتابة سبب الانقطاع',
            'reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
            'semesterId.required' => 'يرجى اختيار الفصل الدراسي',
        ]);

        if ($this->isSelectedSemesterBlocked()) {
            Notification::make()
                ->title('لا يمكن تقديم طلب لهذا الفصل')
                ->body('يوجد طلب سابق مقبول أو مرفوض لهذا الفصل الدراسي.')
                ->danger()
                ->send();

            return;
        }

        $request = SuspensionRequest::create([
            'student_id' => $student->id,
            'semester_id' => $this->semesterId,
            'reason' => $this->reason,
            'status' => SuspensionStatus::Pending,
        ]);

        app(AuditService::class)->log('suspension_requested', $request);

        $this->reason = '';

        Notification::make()->title('تم إرسال طلب الانقطاع للمراجعة')->success()->send();
    }
}
