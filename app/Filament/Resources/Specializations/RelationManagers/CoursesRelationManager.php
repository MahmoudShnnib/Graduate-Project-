<?php

namespace App\Filament\Resources\Specializations\RelationManagers;

use App\Models\Semester;
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
        $activeSemesterId = Semester::activeId();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'courseOfferings' => fn ($q) => $q
                    ->where('semester_id', $activeSemesterId)
                    ->with('faculty:id,name'),
            ]))
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

                TextColumn::make('faculty')
                    ->label('أستاذ المادة')
                    ->getStateUsing(
                        fn ($record): string => $record->courseOfferings->first()?->faculty?->name ?? '—'
                    )
                    ->placeholder('غير مسند'),

                TextColumn::make('credits')
                    ->label('الوحدات')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultGroup(
                Group::make('pivot_type')
                    ->label('النوع')
                    ->getKeyFromRecordUsing(fn ($record): string => $record->pivot->type ?? '')
                    ->getTitleFromRecordUsing(fn ($record): string => match ($record->pivot->type ?? '') {
                        'general' => 'عام (مشترك)',
                        'specialized' => 'تخصصي (إجباري)',
                        'elective' => 'اختياري (اختر اثنين)',
                        default => $record->pivot->type ?? '',
                    })
                    ->collapsible()
            )
            ->defaultSort('code');
    }
}
