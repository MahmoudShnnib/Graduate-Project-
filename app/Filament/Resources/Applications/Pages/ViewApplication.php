<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.resources.applications.pages.view-application';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return $this->record->name ?? (string) $this->record->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
