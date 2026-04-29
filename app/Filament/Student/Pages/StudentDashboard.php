<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\ExceptionalRequest;
use App\Models\Semester;
use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class StudentDashboard extends Page
{
    protected string $view = 'filament.student.pages.dashboard';

    protected static ?string $navigationLabel = 'الرئيسية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public function getStudent(): ?Student
    {
        return auth()->user()->student;
    }

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
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
            ->whereHas('grade', fn ($q) => $q->where('letter', 'F'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->select('course_offerings.course_id')
            ->selectRaw('count(*) as fail_count')
            ->groupBy('course_offerings.course_id')
            ->pluck('fail_count', 'course_offerings.course_id')
            ->all();
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
