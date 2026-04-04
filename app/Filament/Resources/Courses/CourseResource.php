<?php

namespace App\Filament\Resources\Courses;

use App\Enums\CourseType;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'المقررات';

    protected static ?string $modelLabel = 'مقرر';

    protected static ?string $pluralModelLabel = 'المقررات';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المقرر')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('رمز المقرر')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('credits')
                    ->label('الساعات المعتمدة')
                    ->numeric()
                    ->default(3)
                    ->minValue(1)
                    ->maxValue(6)
                    ->required(),

                Select::make('type')
                    ->label('نوع المقرر')
                    ->options(CourseType::class)
                    ->required(),

                Select::make('specialization_id')
                    ->label('التخصص')
                    ->relationship('specialization', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('اتركه فارغاً للمقررات العامة'),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم المقرر')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (CourseType $state): string => $state->label())
                    ->color(fn (CourseType $state): string => match ($state) {
                        CourseType::General => 'info',
                        CourseType::Specialized => 'success',
                        CourseType::Elective => 'warning',
                    }),

                TextColumn::make('specialization.name')
                    ->label('التخصص')
                    ->placeholder('عام'),

                TextColumn::make('credits')
                    ->label('الساعات')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(CourseType::class),

                SelectFilter::make('specialization_id')
                    ->label('التخصص')
                    ->relationship('specialization', 'name'),
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
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }
}
