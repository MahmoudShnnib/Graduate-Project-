<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ApplicationsOverTimeWidget extends ChartWidget
{
    protected ?string $heading = 'طلبات القبول عبر الزمن';

    protected static ?int $sort = 2;

    public ?string $filter = 'daily';

    public static function canView(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'يومي (آخر 14 يوم)',
            'weekly' => 'أسبوعي (آخر 12 أسبوع)',
        ];
    }

    protected function getData(): array
    {
        if ($this->filter === 'weekly') {
            return $this->getWeeklyData();
        }

        return $this->getDailyData();
    }

    private function getDailyData(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $counts = Application::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::today()->subDays(13))
            ->groupBy('date')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'عدد الطلبات',
                    'data' => $days->map(fn ($day) => $counts[$day->toDateString()] ?? 0)->values()->toArray(),
                    'backgroundColor' => 'rgba(31, 78, 121, 0.2)',
                    'borderColor' => 'rgba(31, 78, 121, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('d/m'))->values()->toArray(),
        ];
    }

    private function getWeeklyData(): array
    {
        $weeks = collect(range(11, 0))->map(fn ($i) => Carbon::now()->startOfWeek()->subWeeks($i));

        $counts = Application::selectRaw('YEARWEEK(created_at, 1) as yw, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subWeeks(12))
            ->groupBy('yw')
            ->pluck('count', 'yw');

        return [
            'datasets' => [
                [
                    'label' => 'عدد الطلبات',
                    'data' => $weeks->map(fn ($week) => $counts[$week->format('oW')] ?? 0)->values()->toArray(),
                    'backgroundColor' => 'rgba(31, 78, 121, 0.2)',
                    'borderColor' => 'rgba(31, 78, 121, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $weeks->map(fn ($week) => 'الأسبوع '.$week->format('d/m'))->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
