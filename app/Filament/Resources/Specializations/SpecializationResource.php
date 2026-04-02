<?php

namespace App\Filament\Resources\Specializations;

use App\Filament\Resources\Specializations\Pages\CreateSpecialization;
use App\Filament\Resources\Specializations\Pages\EditSpecialization;
use App\Filament\Resources\Specializations\Pages\ListSpecializations;
use App\Models\Specialization;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpecializationResource extends Resource
{
    protected static ?string $model = Specialization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم التخصص')
                    ->required(),

                TextInput::make('code')
                    ->label('رمز التخصص')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم التخصص')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('رمز التخصص'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecializations::route('/'),
            'create' => CreateSpecialization::route('/create'),
            'edit' => EditSpecialization::route('/{record}/edit'),
        ];
    }
}
