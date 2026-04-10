<?php

namespace App\Filament\Resources\Applications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_name')
                    ->label('اللقب')
                    ->searchable(),
                TextColumn::make('national_id')
                    ->label('الرقم الوطني')
                    ->searchable(),
                TextColumn::make('study_type')
                    ->label('نوع الدراسة')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
