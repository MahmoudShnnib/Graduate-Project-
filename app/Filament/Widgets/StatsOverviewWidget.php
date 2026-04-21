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
    protected function getStats(): array
    {
        $activeSemester = Semester::where('is_active', true)->first();

        $activeStudents = Student::where('status', StudentStatus::Active)->count();
        $inactiveStudents = Student::where('status', StudentStatus::Inactive)->count();
        $withdrawnStudents = Student::where('status', StudentStatus::Withdrawn)->count();

        $pendingApplications = Application::where('status', ApplicationStatus::Pending)->count();
        $pendingGrades = Grade::where('status', GradeStatus::PendingApproval)->count();

        $addDropStatus = $activeSemester?->isAddDropOpen()
            ? 'مفتوحة'
            : 'مغلقة';

        return [
            Stat::make('الطلاب النشطون', $activeStudents)
                ->description("موقوف: {$inactiveStudents} | منسحب: {$withdrawnStudents}")
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
