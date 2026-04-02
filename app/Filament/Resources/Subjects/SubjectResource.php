<?php

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Models\Subject;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'المواد';

    protected static ?string $modelLabel = 'مادة';

    protected static ?string $pluralModelLabel = 'المواد';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المادة')
                    ->required(),

                TextInput::make('code')
                    ->label('رمز المادة')
                    ->required(),

                Select::make('category')
                    ->label('التخصص')
                    ->options([
                        'general' => 'مواد عامة',
                        'cs' => 'مواد الحاسب',
                        'com' => 'مواد الاتصالات',
                        'ctr' => 'مواد التحكم',
                    ])
                    ->required()
                    ->default('general')
                    ->native(false),

                TextInput::make('credits')
                    ->label('عدد الوحدات')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4)
                    ->default(1),

                Checkbox::make('is_elective')
                    ->label('مادة اختيارية'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('رمز المادة')
                    ->searchable(),
                TextColumn::make('credits')
                    ->label('عدد وحداتها')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('التخصص')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'general' => 'مواد عامة',
                        'cs' => 'مواد الحاسب',
                        'com' => 'مواد الاتصالات',
                        'ctr' => 'مواد التحكم',
                        default => $state,
                    }),





            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('التخصص')
                    ->options([
                        'general' => 'مواد عامة',
                        'cs' => 'مواد الحاسب',
                        'com' => 'مواد الاتصالات',
                        'ctr' => 'مواد التحكم',
                    ]),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
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
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }
}
