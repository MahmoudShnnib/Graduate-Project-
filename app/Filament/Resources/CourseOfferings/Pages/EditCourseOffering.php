<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Models\CourseOffering;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseOffering extends EditRecord
{
    protected static string $resource = CourseOfferingResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_if(
            $this->record instanceof CourseOffering && $this->record->semester?->isArchived(),
            403,
            'هذا المقرر في فصل مؤرشف ولا يمكن تعديله',
        );
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
