<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CoursesSurveyResults extends Page
{
    protected string $view = 'filament.pages.courses-survey-results';

    protected static ?string $navigationLabel = 'نتائج استطلاع المقررات';

    protected static ?string $title = 'نتائج استطلاع المقررات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        $activeSemester = Semester::activeId();
        if (! $activeSemester) {
            return null;
        }

        return (string) CourseSurvey::where('semester_id', $activeSemester)
            ->where('is_processed', false)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'info';
    }

    /** @var array<int, int> */
    public array $selectedCourses = [];

    /**
     * Controls visibility of the courses demand aggregation table
     * ("نتائج استطلاع <semester>"). Livewire-persisted via the Session
     * attribute so the admin's preference survives page reloads.
     */
    #[\Livewire\Attributes\Session]
    public bool $showCoursesTable = true;

    public function toggleCoursesTable(): void
    {
        $this->showCoursesTable = ! $this->showCoursesTable;
    }

    public function getActiveSemester(): ?Semester
    {
        return Semester::active();
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

    /**
     * Students who have submitted at least one survey entry this semester,
     * with total/processed counts so the admin can see at-a-glance who's
     * been fully processed vs who still has pending choices.
     *
     * Merged from the retired StudentSurveysList page (T10, 2026-04-20).
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    public function getStudentsWithSurveys(): Collection
    {
        $semester = $this->getActiveSemester();

        if (! $semester) {
            return collect();
        }

        return Student::query()
            ->whereHas('courseSurveys', fn ($q) => $q->where('semester_id', $semester->id))
            ->with(['user:id,name', 'specialization:id,name'])
            ->withCount([
                'courseSurveys as course_surveys_count' => fn ($q) => $q->where('semester_id', $semester->id),
                'courseSurveys as processed_surveys_count' => fn ($q) => $q->where('semester_id', $semester->id)->where('is_processed', true),
            ])
            ->orderBy('id')
            ->get();
    }

    public function getStudentSurveyUrl(int $studentId): string
    {
        $semester = $this->getActiveSemester();

        return url("/admin/student-survey/{$studentId}/{$semester?->id}");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_selected')
                ->label('فتح المقررات المختارة')
                ->icon(Heroicon::LockOpen)
                ->color('success')
                ->disabled(fn (): bool => empty($this->selectedCourses))
                ->modalHeading('فتح المقررات المختارة')
                ->modalDescription('اختر عضو هيئة التدريس لكل مقرر قبل الفتح. سيتم فتح المقررات في الفصل النشط الحالي.')
                ->modalWidth('3xl')
                ->modalSubmitActionLabel('فتح')
                ->modalCancelActionLabel('إلغاء')
                ->fillForm(fn (): array => [
                    'courses' => collect($this->selectedCourses)
                        ->map(fn (int $courseId): array => [
                            'course_id' => $courseId,
                            'faculty_id' => null,
                            'is_open' => true,
                        ])
                        ->values()
                        ->all(),
                ])
                ->form([
                    Repeater::make('courses')
                        ->label('')
                        ->schema([
                            Select::make('course_id')
                                ->label('المقرر')
                                ->options(fn () => Course::query()->pluck('name', 'id'))
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(2),

                            Select::make('faculty_id')
                                ->label('عضو هيئة التدريس')
                                ->options(fn () => User::query()
                                    ->where('role', 'faculty')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('غير مسند'),

                            Toggle::make('is_open')
                                ->label('مفتوح للتسجيل')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    $activeSemester = $this->getActiveSemester();

                    if (! $activeSemester) {
                        Notification::make()->title('لا يوجد فصل نشط')->danger()->send();

                        return;
                    }

                    $count = 0;
                    foreach ($data['courses'] ?? [] as $courseData) {
                        $offering = CourseOffering::firstOrCreate([
                            'course_id' => $courseData['course_id'],
                            'semester_id' => $activeSemester->id,
                        ]);

                        $offering->update([
                            'faculty_id' => $courseData['faculty_id'] ?: null,
                            'is_open' => (bool) ($courseData['is_open'] ?? true),
                        ]);

                        $count++;
                    }

                    $this->selectedCourses = [];

                    Notification::make()
                        ->title("تم فتح {$count} مقرر بنجاح")
                        ->success()
                        ->send();
                }),
        ];
    }
}
