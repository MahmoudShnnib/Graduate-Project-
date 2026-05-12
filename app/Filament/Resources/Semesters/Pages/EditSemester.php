<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Filament\Resources\Semesters\SemesterResource;
use App\Models\Semester;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSemester extends EditRecord
{
    protected static string $resource = SemesterResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_if(
            $this->record instanceof Semester && $this->record->isArchived(),
            403,
            'هذا الفصل مؤرشف ولا يمكن تعديله',
        );
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
    // Note: previously this page also flipped is_active=false on other
    // semesters in mutateFormDataBeforeSave. That behavior moved to
    // SemesterObserver::archivePeersOf so EVERY entry point (this page,
    // CreateSemester, seeders, factories, direct updates) enforces the
    // one-active-semester invariant uniformly.
}
