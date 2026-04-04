<?php

namespace App\Filament\Student\Pages;

use App\Models\Application;
use Filament\Pages\Page;

class ApplicationStatusPage extends Page
{
    protected string $view = 'filament.student.pages.application-status';

    protected static ?string $navigationLabel = 'حالة الطلب';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public function getApplication(): ?Application
    {
        return Application::where('user_id', auth()->id())
            ->latest()
            ->first();
    }
}
