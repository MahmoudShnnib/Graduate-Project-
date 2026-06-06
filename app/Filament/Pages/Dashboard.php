<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardHeaderWidget;
use App\Filament\Widgets\DashboardOverviewWidget;
use App\Filament\Widgets\Faculty\FacultyStatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            DashboardHeaderWidget::class,
            DashboardOverviewWidget::class,
            FacultyStatsOverviewWidget::class,
        ];
    }
}
