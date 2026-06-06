<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Keep Spatie role in sync if the admin changes the role column.
     */
    protected function afterSave(): void
    {
        $role = $this->record->role;

        if (in_array($role, ['super_admin', 'manager_assistant', 'faculty', 'student'], true)) {
            $this->record->syncRoles([$role]);
        }
    }
}
