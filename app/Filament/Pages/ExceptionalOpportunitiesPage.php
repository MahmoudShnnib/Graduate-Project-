<?php

namespace App\Filament\Pages;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\ExceptionalRequest;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentWarning;
use App\Services\WarningService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ExceptionalOpportunitiesPage extends Page
{
    protected string $view = 'filament.pages.exceptional-opportunities';

    protected static ?string $navigationLabel = 'فرص استثنائية';

    protected static ?string $title = 'فرص استثنائية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 10;

    public ?int $viewingStudentId = null;

    public string $summonMessage = '';

    public ?int $summonRequestId = null;

    public function getDismissedWithoutRequest(): Collection
    {
        return Student::where('status', StudentStatus::Dismissed)
            ->with(['user', 'specialization', 'dismissalCourse'])
            ->whereDoesntHave('exceptionalRequests', fn ($q) => $q->whereIn('status', [
                ExceptionalRequestStatus::Pending,
                ExceptionalRequestStatus::Summoned,
            ]))
            ->get();
    }

    public function getDismissedWithPendingRequest(): Collection
    {
        return Student::where('status', StudentStatus::Dismissed)
            ->with(['user', 'specialization', 'dismissalCourse', 'exceptionalRequests' => fn ($q) => $q->latest()])
            ->whereHas('exceptionalRequests', fn ($q) => $q->whereIn('status', [
                ExceptionalRequestStatus::Pending,
                ExceptionalRequestStatus::Summoned,
            ]))
            ->get();
    }

    public function getDismissalReasonText(Student $student): string
    {
        return match ($student->dismissal_type) {
            WarningType::CourseFailure => 'رسوب في مادة: '.($student->dismissalCourse?->name ?? 'غير محددة'),
            WarningType::ConsecutiveInactive => 'انقطاع متتالي عن الدراسة',
            default => 'غير محدد',
        };
    }

    public function getLatestRequest(Student $student): ?ExceptionalRequest
    {
        return $student->exceptionalRequests->first();
    }

    public function viewStudent(int $studentId): void
    {
        $this->viewingStudentId = $studentId;
    }

    public function backToList(): void
    {
        $this->viewingStudentId = null;
    }

    public function getViewingStudent(): ?Student
    {
        if (! $this->viewingStudentId) {
            return null;
        }

        return Student::with(['user', 'specialization', 'dismissalCourse', 'activeWarnings.course', 'activeWarnings.semester', 'exceptionalRequests' => fn ($q) => $q->latest()])
            ->find($this->viewingStudentId);
    }

    public function acceptRequest(int $requestId): void
    {
        $request = ExceptionalRequest::with('student.dismissalCourse')->findOrFail($requestId);
        $student = $request->student;

        $request->update([
            'status' => ExceptionalRequestStatus::Accepted,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        if ($student->dismissal_type === WarningType::CourseFailure && $student->dismissal_course_id) {
            StudentWarning::where('student_id', $student->id)
                ->where('type', WarningType::CourseFailure)
                ->where('course_id', $student->dismissal_course_id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'resolved_at' => now()]);

            $semester = Semester::where('is_active', true)->first();

            StudentWarning::create([
                'student_id' => $student->id,
                'type' => WarningType::CourseFailure,
                'course_id' => $student->dismissal_course_id,
                'semester_id' => $semester?->id ?? $student->warnings()->latest()->first()?->semester_id,
                'has_exceptional_chance' => true,
            ]);
        }

        if ($student->dismissal_type === WarningType::ConsecutiveInactive) {
            app(WarningService::class)->clearInactiveWarnings($student);
        }

        $student->update([
            'status' => StudentStatus::Active,
            'dismissed_at' => null,
            'dismissal_type' => null,
            'dismissal_course_id' => null,
        ]);

        app(WarningService::class)->syncWarningsCount($student);

        $this->viewingStudentId = null;

        Notification::make()
            ->title('تم قبول الطلب الاستثنائي')
            ->body('تم إعادة الطالب '.$student->user->name.' إلى الحالة النشطة.')
            ->success()
            ->send();
    }

    public function rejectRequest(int $requestId): void
    {
        $request = ExceptionalRequest::findOrFail($requestId);

        $request->update([
            'status' => ExceptionalRequestStatus::Rejected,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        $this->viewingStudentId = null;

        Notification::make()
            ->title('تم رفض الطلب الاستثنائي')
            ->danger()
            ->send();
    }

    public function openSummonModal(int $requestId): void
    {
        $this->summonRequestId = $requestId;
        $this->summonMessage = '';
        $this->dispatch('open-modal', id: 'summon-modal');
    }

    public function sendSummon(): void
    {
        if (! $this->summonRequestId || blank($this->summonMessage)) {
            Notification::make()->title('يرجى كتابة رسالة الاستدعاء')->warning()->send();

            return;
        }

        $request = ExceptionalRequest::findOrFail($this->summonRequestId);

        $request->update([
            'status' => ExceptionalRequestStatus::Summoned,
            'summon_message' => $this->summonMessage,
            'summon_sent_at' => now(),
        ]);

        $this->summonRequestId = null;
        $this->summonMessage = '';
        $this->dispatch('close-modal', id: 'summon-modal');

        Notification::make()
            ->title('تم إرسال الاستدعاء')
            ->success()
            ->send();
    }
}
