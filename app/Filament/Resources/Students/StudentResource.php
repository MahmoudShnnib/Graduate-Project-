<?php

namespace App\Filament\Resources\Students;

use App\Enums\StudentStatus;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'enrollment_no';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('enrollment_no')
                    ->label('رقم القيد')
                    ->disabled()
                    ->placeholder('سيتم توليد رقم القيد تلقائياً')
                    ->dehydrated(),

                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('المستخدم')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص')
                    ->required(),

                Select::make('status')
                    ->label('الحالة')
                    ->options(StudentStatus::class)
                    ->default(StudentStatus::Active)
                    ->required(),

                TextInput::make('gpa')
                    ->label('المعدل التراكمي')
                    ->numeric()
                    ->disabled(),

                TextInput::make('warnings_count')
                    ->label('عدد الإنذارات')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('enrollment_no')
                    ->label('رقم القيد')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('specialization.name')
                    ->label('التخصص'),

                TextColumn::make('gpa')
                    ->label('المعدل')
                    ->badge()
                    ->color(fn (string $state): string => (float) $state >= 3.0 ? 'success' : 'danger'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(StudentStatus::class),
                SelectFilter::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص'),
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
