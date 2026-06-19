<?php

namespace App\Filament\Student\Pages;

use App\Enums\DiscussionStatus;
use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\ThesisStatus;
use App\Enums\WarningType;
use App\Models\DiscussionRequest;
use App\Models\ExceptionalRequest;
use App\Models\Semester;
use App\Models\Student;
use App\Services\TelegramVerificationService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class StudentDashboard extends Page
{
    protected string $view = 'filament.student.pages.dashboard';

    public ?string $telegramDeepLink = null;

    public function telegramLinkingEnabled(): bool
    {
        return app(TelegramVerificationService::class)->isConfigured();
    }

    public function isTelegramLinked(): bool
    {
        return ! empty(auth()->user()->fresh()->telegram_chat_id);
    }

    public function startTelegramLink(): void
    {
        $service = app(TelegramVerificationService::class);
        $this->telegramDeepLink = $service->deepLink($service->startForUser(auth()->user()));
    }

    public function unlinkTelegram(): void
    {
        app(TelegramVerificationService::class)->unlinkUser(auth()->user()->fresh());
        $this->telegramDeepLink = null;
    }

    public function unlinkTelegramAction(): Action
    {
        return Action::make('unlinkTelegram')
            ->label('إلغاء')
            ->link()
            ->color('danger')
            ->size('xs')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedXCircle)
            ->modalHeading('إلغاء ربط حساب تيليجرام')
            ->modalDescription('سيتوقف وصول إشعارات الدراسات العليا إلى تيليجرام. يمكنك إعادة الربط في أي وقت.')
            ->modalSubmitActionLabel('نعم، إلغاء الربط')
            ->modalCancelActionLabel('تراجع')
            ->action(fn () => $this->unlinkTelegram());
    }

    protected static ?string $navigationLabel = 'الرئيسية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public function getStudent(): ?Student
    {
        return auth()->user()->student;
    }

    private ?Semester $resolvedSemester = null;

    public function getActiveSemester(): ?Semester
    {
        return $this->resolvedSemester ??= Semester::active();
    }

    public function getActiveSummon(): ?ExceptionalRequest
    {
        $student = $this->getStudent();

        if (! $student) {
            return null;
        }

        return $student->exceptionalRequests()
            ->where('status', ExceptionalRequestStatus::Summoned)
            ->whereNotNull('summon_message')
            ->latest()
            ->first();
    }

    public function getActiveWarnings(): Collection
    {
        $student = $this->getStudent();

        if (! $student) {
            return collect();
        }

        return $student->activeWarnings()->with('course', 'semester')->get();
    }

    public function getCourseFailCounts(): array
    {
        $student = $this->getStudent();

        if (! $student) {
            return [];
        }

        return $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at')->where('letter', 'F'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->select('course_offerings.course_id')
            ->selectRaw('count(*) as fail_count')
            ->groupBy('course_offerings.course_id')
            ->pluck('fail_count', 'course_offerings.course_id')
            ->all();
    }

    public function getActiveDiscussionRequest(): ?DiscussionRequest
    {
        $student = $this->getStudent();

        if (! $student) {
            return null;
        }

        return $student->discussionRequests()
            ->whereNotIn('status', [
                DiscussionStatus::Rejected,
                DiscussionStatus::DiscussionRejected,
            ])
            ->latest()
            ->first();
    }

    public function isThesisApprovedWithoutDiscussion(): bool
    {
        $student = $this->getStudent();

        if (! $student) {
            return false;
        }

        $thesis = $student->latestThesisRegistration;

        if (! $thesis || $thesis->status !== ThesisStatus::Approved) {
            return false;
        }

        return ! $this->getActiveDiscussionRequest();
    }

    public function getDismissalReasonText(): ?string
    {
        $student = $this->getStudent();

        if (! $student || $student->status !== StudentStatus::Dismissed) {
            return null;
        }

        return match ($student->dismissal_type) {
            WarningType::CourseFailure => 'رسوب في مادة: '.($student->dismissalCourse?->name ?? 'غير محددة'),
            WarningType::ConsecutiveInactive => 'انقطاع متتالي عن الدراسة',
            default => 'غير محدد',
        };
    }
}
