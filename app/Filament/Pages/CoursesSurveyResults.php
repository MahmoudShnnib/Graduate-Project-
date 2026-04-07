<?php

namespace App\Filament\Pages;

use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CoursesSurveyResults extends Page
{
    protected string $view = 'filament.pages.courses-survey-results';

    protected static ?string $navigationLabel = 'نتائج استطلاع المقررات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 10;

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function getSurveyResults(): Collection
    {
        $activeSemester = $this->getActiveSemester();

        if (! $activeSemester) {
            return collect();
        }

        return CourseSurvey::where('semester_id', $activeSemester->id)
            ->with('course.specializations')
            ->selectRaw('course_id, COUNT(*) as demand_count')
            ->selectRaw('SUM(CASE WHEN priority = 1 THEN 1 ELSE 0 END) as priority_1_count')
            ->selectRaw('SUM(CASE WHEN priority = 2 THEN 1 ELSE 0 END) as priority_2_count')
            ->selectRaw('SUM(CASE WHEN priority = 3 THEN 1 ELSE 0 END) as priority_3_count')
            ->selectRaw('SUM(CASE WHEN priority = 4 THEN 1 ELSE 0 END) as priority_4_count')
            ->groupBy('course_id')
            ->orderByDesc('demand_count')
            ->get();
    }

    public function getEnrollStudentsUrl(int $courseId): ?string
    {
        $semester = $this->getActiveSemester();

        if (! $semester) {
            return null;
        }

        $offering = CourseOffering::where('course_id', $courseId)
            ->where('semester_id', $semester->id)
            ->first();

        if (! $offering) {
            return null;
        }

        return url("/admin/course-offerings/{$offering->id}/enroll");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_selected')
                ->label('فتح المقررات المختارة')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $activeSemester = $this->getActiveSemester();

                    if (! $activeSemester) {
                        Notification::make()->title('لا يوجد فصل نشط')->danger()->send();

                        return;
                    }

                    $surveyResults = $this->getSurveyResults();

                    foreach ($surveyResults as $result) {
                        $offering = CourseOffering::firstOrCreate([
                            'course_id' => $result->course_id,
                            'semester_id' => $activeSemester->id,
                        ]);

                        $offering->update(['is_open' => true]);
                    }

                    Notification::make()->title('تم فتح المقررات بنجاح')->success()->send();
                }),
        ];
    }
}
