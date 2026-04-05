<?php

namespace App\Filament\Resources\Specializations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    protected static ?string $title = 'المقررات';

    protected static ?string $modelLabel = 'مقرر';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الرمز')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('اسم المقرر')
                    ->searchable(),

                TextColumn::make('pivot.type')
                    ->label('النوع')
                    ->badge()
                    ->getStateUsing(fn ($record): string => $record->pivot->type ?? '')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'عام',
                        'specialized' => 'تخصصي (إجباري)',
                        'elective' => 'اختياري',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'info',
                        'specialized' => 'success',
                        'elective' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('credits')
                    ->label('الساعات')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultGroup(
                Group::make('pivot.type')
                    ->label('النوع')
                    ->getTitleFromRecordUsing(fn ($record): string => match ($record->pivot->type ?? '') {
                        'general' => '📘 عام (مشترك)',
                        'specialized' => '📗 تخصصي (إجباري)',
                        'elective' => '📙 اختياري (اختر اثنين)',
                        default => $record->pivot->type ?? '',
                    })
                    ->collapsible()
            )
            ->defaultSort('code');
    }
}
