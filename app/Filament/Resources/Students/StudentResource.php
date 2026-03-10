<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Student;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $recordTitleAttribute = 'student_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_number')
                    ->label('رقم القيد')
                    ->disabled()
                    ->placeholder('سيتم توليد رقم القيد تلقائياً')
                    ->dehydrated(),

                Select::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص')
                    ->required(),

                TextInput::make('admission_year')
                    ->label('سنة القبول'),

                Select::make('semester_type')
                    ->label('نوع الفصل')
                    ->options([
                        1 => 'خريف',
                        2 => 'ربيع',
                        3 => 'خريف منتقل',
                        4 => 'ربيع منتقل',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.full_name')
                    ->label('اسم الطالب'),

                TextColumn::make('student_number')
                    ->label('رقم القيد')
                    ->searchable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص'),

                TextColumn::make('admission_year')
                    ->label('سنة القبول'),

                TextColumn::make('semester_type')
                    ->label('الفصل')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'خريف',
                        2 => 'ربيع',
                        3 => 'خريف منتقل',
                        4 => 'ربيع منتقل',
                        default => $state,
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
