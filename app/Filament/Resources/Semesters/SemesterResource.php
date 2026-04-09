<?php

namespace App\Filament\Resources\Semesters;

use App\Enums\Season;
use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Filament\Resources\Semesters\Pages\EditSemester;
use App\Filament\Resources\Semesters\Pages\ListSemesters;
use App\Models\Course;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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

                Toggle::make('survey_open')
                    ->label('فتح الاستطلاع للطلاب')
                    ->helperText('عند التفعيل يستطيع الطلاب اختيار مقرراتهم المفضلة'),
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

                IconColumn::make('is_active')
                    ->label('الحالي')
                    ->boolean(),

                IconColumn::make('survey_open')
                    ->label('الاستطلاع')
                    ->boolean()
                    ->trueIcon(Heroicon::LockOpen)
                    ->falseIcon(Heroicon::LockClosed)
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('surveys_count')
                    ->label('ردود الاستطلاع')
                    ->counts('surveys')
                    ->badge()
                    ->color('info'),
            ])
            ->recordActions([
                Action::make('open_survey')
                    ->label('فتح الاستطلاع')
                    ->icon(Heroicon::LockOpen)
                    ->color('success')
                    ->modalHeading('فتح الاستطلاع — اختيار المقررات المتاحة')
                    ->modalDescription('اختر المقررات التي ستكون متاحة للطلاب في هذا الفصل.')
                    ->modalSubmitActionLabel('فتح الاستطلاع')
                    ->fillForm(fn (Semester $record): array => [
                        'course_ids' => $record->courses()->pluck('courses.id')->toArray(),
                    ])
                    ->schema([
                        CheckboxList::make('course_ids')
                            ->label('المقررات')
                            ->options(fn () => Course::where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->name.' — '.$c->code]))
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable()
                            ->required(),
                    ])
                    ->action(function (Semester $record, array $data) {
                        $record->courses()->sync($data['course_ids']);
                        $record->update(['survey_open' => true]);

                        Notification::make()
                            ->title('تم فتح الاستطلاع وتحديد '.count($data['course_ids']).' مقرر')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Semester $record) => ! $record->survey_open),

                Action::make('edit_survey_courses')
                    ->label('تعديل المقررات')
                    ->icon(Heroicon::Pencil)
                    ->color('info')
                    ->modalHeading('تعديل المقررات المتاحة للاستطلاع')
                    ->modalSubmitActionLabel('حفظ التعديلات')
                    ->fillForm(fn (Semester $record): array => [
                        'course_ids' => $record->courses()->pluck('courses.id')->toArray(),
                    ])
                    ->schema([
                        CheckboxList::make('course_ids')
                            ->label('المقررات')
                            ->options(fn () => Course::where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->name.' — '.$c->code]))
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable()
                            ->required(),
                    ])
                    ->action(function (Semester $record, array $data) {
                        $record->courses()->sync($data['course_ids']);

                        Notification::make()
                            ->title('تم تحديث المقررات المتاحة')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Semester $record) => $record->survey_open),

                Action::make('close_survey')
                    ->label('إغلاق الاستطلاع')
                    ->icon(Heroicon::LockClosed)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إغلاق الاستطلاع')
                    ->modalDescription('سيتم إغلاق الاستطلاع ولن يتمكن الطلاب من تعديل اختياراتهم.')
                    ->action(function (Semester $record) {
                        $record->update(['survey_open' => false]);

                        Notification::make()
                            ->title('تم إغلاق الاستطلاع')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Semester $record) => $record->survey_open),

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
