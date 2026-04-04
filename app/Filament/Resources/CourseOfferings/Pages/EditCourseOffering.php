<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseOffering extends EditRecord
{
    protected static string $resource = CourseOfferingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
