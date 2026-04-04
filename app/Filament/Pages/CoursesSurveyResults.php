<?php

namespace App\Filament\Pages;

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
            ->with('course')
            ->selectRaw('course_id, COUNT(*) as demand_count')
            ->groupBy('course_id')
            ->orderByDesc('demand_count')
            ->get();
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
                        $offering = \App\Models\CourseOffering::firstOrCreate([
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
