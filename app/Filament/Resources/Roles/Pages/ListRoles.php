<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles as ShieldListRoles;

/**
 * Local subclass that rebinds $resource to our overriding RoleResource so
 * Filament resolves the route → page → resource → table chain back to our
 * translated columns (audit fix M-4). Everything else inherits from Shield.
 */
class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;
}
