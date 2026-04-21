<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Sync the Spatie role to match the role column. Without this, the
     * HasPanelShield trait auto-assigns 'panel_user' and Shield policy
     * checks (e.g. can('ViewAny:CourseOffering')) fail.
     */
    protected function afterCreate(): void
    {
        $role = $this->record->role;

        if (in_array($role, ['super_admin', 'secretary', 'faculty', 'student'], true)) {
            $this->record->syncRoles([$role]);
        }
    }
}
