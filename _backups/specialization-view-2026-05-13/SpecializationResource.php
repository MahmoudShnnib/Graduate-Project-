<?php

namespace App\Filament\Resources\Specializations;

use App\Filament\Resources\Specializations\Pages\CreateSpecialization;
use App\Filament\Resources\Specializations\Pages\EditSpecialization;
use App\Filament\Resources\Specializations\Pages\ListSpecializations;
use App\Filament\Resources\Specializations\Pages\ViewSpecialization;
use App\Filament\Resources\Specializations\RelationManagers\CoursesRelationManager;
use App\Filament\Resources\Specializations\Schemas\SpecializationForm;
use App\Models\Specialization;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpecializationResource extends Resource
{
    protected static ?string $model = Specialization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'التخصصات';

    protected static ?string $modelLabel = 'تخصص';

    protected static ?string $pluralModelLabel = 'التخصصات';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->components([
                    TextEntry::make('name')
                        ->label('اسم التخصص'),

                    TextEntry::make('code')
                        ->label('رمز التخصص (P)'),

                    TextEntry::make('courses_count')
                        ->label('عدد المقررات')
                        ->getStateUsing(fn (Specialization $record): int => $record->courses()->count()),

                    TextEntry::make('students_count')
                        ->label('عدد الطلاب')
                        ->getStateUsing(fn (Specialization $record): int => $record->students()->count()),
                ]),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SpecializationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم التخصص')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('رمز التخصص (P)'),

                TextColumn::make('courses_count')
                    ->label('عدد المقررات')
                    ->counts('courses'),

                TextColumn::make('students_count')
                    ->label('عدد الطلاب')
                    ->counts('students'),
            ])
            ->recordUrl(fn (Specialization $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CoursesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecializations::route('/'),
            'create' => CreateSpecialization::route('/create'),
            'view' => ViewSpecialization::route('/{record}'),
            'edit' => EditSpecialization::route('/{record}/edit'),
        ];
    }
}
