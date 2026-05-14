<?php

namespace App\Filament\Resources\Specializations\Pages;

use App\Filament\Resources\Specializations\SpecializationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSpecialization extends ViewRecord
{
    protected static string $resource = SpecializationResource::class;

    protected string $view = 'filament.resources.specializations.pages.view-specialization';

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

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
