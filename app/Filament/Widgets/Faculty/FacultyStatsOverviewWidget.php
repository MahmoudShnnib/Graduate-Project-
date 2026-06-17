<?php

namespace App\Filament\Widgets\Faculty;

use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FacultyStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasRole('faculty');
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $activeSemester = Semester::active();
        $activeSemesterId = $activeSemester?->id;

        $myOfferings = CourseOffering::query()
            ->where('faculty_id', $userId)
            ->when($activeSemesterId, fn ($q) => $q->where('semester_id', $activeSemesterId));

        $myCoursesCount = (clone $myOfferings)->count();

        $myStudentsCount = StudentEnrollment::query()
            ->active()
            ->whereHas('courseOffering', function ($q) use ($userId, $activeSemesterId): void {
                $q->where('faculty_id', $userId);
                if ($activeSemesterId) {
                    $q->where('semester_id', $activeSemesterId);
                }
            })
            ->count();

        $addDropStatus = $activeSemester?->isAddDropOpen() ? 'مفتوحة' : 'مغلقة';

        return [
            Stat::make('مقرراتي هذا الفصل', $myCoursesCount)
                ->color('primary')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('طلابي', $myStudentsCount)
                ->color('info')
                ->icon('heroicon-o-user-group'),

            Stat::make('الفصل الحالي', $activeSemester?->name ?? 'لا يوجد')
                ->description("فترة الإضافة/الحذف: {$addDropStatus}")
                ->color($activeSemester ? 'success' : 'danger')
                ->icon('heroicon-o-calendar'),
        ];
    }
}
