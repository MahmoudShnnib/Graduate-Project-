<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Local override of Shield's RoleResource.
 *
 * Shield's plugin checks the panel's resources collection for any class
 * ending in `\RoleResource` and, when present, skips registering its own.
 * That lets us replace the vendor resource without conflict — Filament's
 * `discoverResources()` picks this class up automatically.
 *
 * Overrides:
 *   - role names render translated (`super_admin` → `مشرف عام`) via lang/ar/roles.php
 *   - the `guard_name` column is hidden (system uses a single `web` guard)
 *
 * Everything else (form, pages, slug, navigation label) is inherited from
 * the vendor resource. Audit fix M-4 (UX audit 2026-05-15).
 */
class RoleResource extends ShieldRoleResource
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn (string $state): string => __('roles.'.$state, [], 'ar') !== 'roles.'.$state
                        ? __('roles.'.$state)
                        : \Illuminate\Support\Str::headline($state))
                    ->searchable(),

                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),

                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
