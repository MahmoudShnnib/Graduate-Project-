<?php

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusWidget extends ChartWidget
{
    protected ?string $heading = 'نسبة القبول والرفض';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->role !== 'faculty';
    }

    protected function getData(): array
    {
        $accepted = Application::where('status', ApplicationStatus::Accepted)->count();
        $rejected = Application::where('status', ApplicationStatus::Rejected)->count();
        $pending = Application::where('status', ApplicationStatus::Pending)->count();

        return [
            'datasets' => [
                [
                    'data' => [$accepted, $rejected, $pending],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.85)',   // green — accepted
                        'rgba(239, 68, 68, 0.85)',   // red   — rejected
                        'rgba(234, 179, 8, 0.85)',   // yellow — pending
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['مقبول', 'مرفوض', 'قيد الانتظار'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
