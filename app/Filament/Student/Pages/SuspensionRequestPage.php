<?php

namespace App\Filament\Student\Pages;

use App\Enums\SuspensionStatus;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SuspensionRequestPage extends Page
{
    protected string $view = 'filament.student.pages.suspension-request';

    protected static ?string $navigationLabel = 'طلب الانقطاع';

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

    public function submit(): void
    {
        $student = auth()->user()->student;

        if (! $student) {
            Notification::make()->title('لا يوجد سجل طالب')->danger()->send();

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

        SuspensionRequest::create([
            'student_id' => $student->id,
            'semester_id' => $this->semesterId,
            'reason' => $this->reason,
            'status' => SuspensionStatus::Pending,
        ]);

        $this->reason = '';

        Notification::make()->title('تم إرسال طلب الانقطاع للمراجعة')->success()->send();
    }
}
