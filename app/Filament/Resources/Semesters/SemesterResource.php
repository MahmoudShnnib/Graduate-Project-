<?php

namespace App\Filament\Resources\Semesters;

use App\Enums\Season;
use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Filament\Resources\Semesters\Pages\EditSemester;
use App\Filament\Resources\Semesters\Pages\ListSemesters;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'الفصول الدراسية';

    protected static ?string $modelLabel = 'فصل دراسي';

    protected static ?string $pluralModelLabel = 'الفصول الدراسية';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الفصل')
                    ->required()
                    ->maxLength(255),

                TextInput::make('year')
                    ->label('السنة')
                    ->numeric()
                    ->required(),

                Select::make('season')
                    ->label('الموسم')
                    ->options(Season::class)
                    ->required(),

                DatePicker::make('start_date')
                    ->label('تاريخ البداية')
                    ->required(),

                DatePicker::make('end_date')
                    ->label('تاريخ النهاية')
                    ->required(),

                DatePicker::make('add_drop_start')
                    ->label('بداية فترة الإضافة والحذف'),

                DatePicker::make('add_drop_end')
                    ->label('نهاية فترة الإضافة والحذف'),

                Toggle::make('is_active')
                    ->label('الفصل الحالي')
                    ->helperText('يمكن أن يكون فصل واحد فقط نشطاً في نفس الوقت'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الفصل')
                    ->searchable(),

                TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),

                TextColumn::make('season')
                    ->label('الموسم')
                    ->formatStateUsing(fn (Season $state): string => $state->label()),

                TextColumn::make('start_date')
                    ->label('البداية')
                    ->date(),

                TextColumn::make('end_date')
                    ->label('النهاية')
                    ->date(),

                TextColumn::make('add_drop_start')
                    ->label('بداية الإضافة/الحذف')
                    ->date()
                    ->placeholder('-'),

                TextColumn::make('add_drop_end')
                    ->label('نهاية الإضافة/الحذف')
                    ->date()
                    ->placeholder('-'),

                IconColumn::make('is_active')
                    ->label('الحالي')
                    ->boolean(),
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
            'index' => ListSemesters::route('/'),
            'create' => CreateSemester::route('/create'),
            'edit' => EditSemester::route('/{record}/edit'),
        ];
    }
}
