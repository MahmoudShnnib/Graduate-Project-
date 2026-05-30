<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Models\Application;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    protected function getStats(): array
    {
        $activeSemester = Semester::where('is_active', true)->first();

        $activeStudents = Student::where('status', StudentStatus::Active)->count();
        $onHoldStudents = Student::where('status', StudentStatus::OnHold)->count();
        $inactiveStudents = Student::where('status', StudentStatus::Inactive)->count();
        $transferredStudents = Student::where('status', StudentStatus::Transferred)->count();
        $dismissedStudents = Student::where('status', StudentStatus::Dismissed)->count();

        $pendingApplications = Application::where('status', ApplicationStatus::Pending)->count();
        $pendingGrades = Grade::where('status', GradeStatus::PendingApproval)->count();

        $addDropStatus = $activeSemester?->isAddDropOpen()
            ? 'مفتوحة'
            : 'مغلقة';

        return [
            Stat::make('الطلاب المستمرون', $activeStudents)
                ->description("موقوف: {$onHoldStudents} | منقطع: {$inactiveStudents} | منتقل: {$transferredStudents} | مفصول: {$dismissedStudents}")
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Stat::make('طلبات القبول المعلقة', $pendingApplications)
                ->color('warning')
                ->icon('heroicon-o-document-text'),

            Stat::make('درجات بانتظار الاعتماد', $pendingGrades)
                ->color('info')
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make('الفصل الحالي', $activeSemester?->name ?? 'لا يوجد')
                ->description("فترة الإضافة/الحذف: {$addDropStatus}")
                ->color($activeSemester ? 'success' : 'danger')
                ->icon('heroicon-o-calendar'),
        ];
    }
}
