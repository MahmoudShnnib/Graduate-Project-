<?php

namespace App\Filament\Pages;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\ExceptionalRequest;
use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ExceptionalOpportunitiesPage extends Page
{
    protected string $view = 'filament.pages.exceptional-opportunities';

    protected static ?string $navigationLabel = 'فرص استثنائية';

    protected static ?string $title = 'فرص استثنائية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'شؤون الطلاب';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('View:ExceptionalOpportunitiesPage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->can('View:ExceptionalOpportunitiesPage');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) ExceptionalRequest::where('status', ExceptionalRequestStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

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

    public function getRevivedStudents(): Collection
    {
        return Student::where('status', '!=', StudentStatus::Dismissed)
            ->with(['user', 'specialization', 'exceptionalRequests' => fn ($q) => $q->where('status', ExceptionalRequestStatus::Accepted)->latest()])
            ->whereHas('exceptionalRequests', fn ($q) => $q->where('status', ExceptionalRequestStatus::Accepted))
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

    public static function getStudentUrl(int $studentId): string
    {
        return ViewExceptionalStudent::getUrl(['student' => $studentId]);
    }
}
