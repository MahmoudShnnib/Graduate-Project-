<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApplicationsOverTimeWidget;
use App\Filament\Widgets\ApplicationStatusWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\StudentsPerSpecializationWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            ApplicationsOverTimeWidget::class,
            ApplicationStatusWidget::class,
            StudentsPerSpecializationWidget::class,
        ];
    }
}
