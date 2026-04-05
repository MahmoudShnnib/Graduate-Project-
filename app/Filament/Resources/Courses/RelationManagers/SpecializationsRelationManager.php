<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpecializationsRelationManager extends RelationManager
{
    protected static string $relationship = 'specializations';

    protected static ?string $title = 'التخصصات';

    protected static ?string $modelLabel = 'تخصص';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('الدور في هذا التخصص')
                ->options([
                    'general' => 'عام (إجباري للجميع)',
                    'specialized' => 'تخصصي (إجباري)',
                    'elective' => 'اختياري',
                ])
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('التخصص'),

                TextColumn::make('pivot.type')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'عام',
                        'specialized' => 'تخصصي',
                        'elective' => 'اختياري',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'info',
                        'specialized' => 'success',
                        'elective' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('type')
                            ->label('الدور في هذا التخصص')
                            ->options([
                                'general' => 'عام (إجباري للجميع)',
                                'specialized' => 'تخصصي (إجباري)',
                                'elective' => 'اختياري',
                            ])
                            ->required(),
                    ]),
                DetachBulkAction::make(),
            ]);
    }
}
