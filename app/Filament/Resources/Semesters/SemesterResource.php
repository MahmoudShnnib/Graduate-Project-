<?php

namespace App\Filament\Resources\Semesters;

use App\Enums\Season;
use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Filament\Resources\Semesters\Pages\EditSemester;
use App\Filament\Resources\Semesters\Pages\ListSemesters;
use App\Filament\Resources\Semesters\Pages\ViewSemester;
use App\Filament\Resources\Semesters\RelationManagers\CourseOfferingsRelationManager;
use App\Filament\Resources\Semesters\RelationManagers\DecidedApplicationsRelationManager;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Specialization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Right column (RTL): semester identity
                \Filament\Schemas\Components\Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('معلومات الفصل')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-info'])
                            ->components([
                                TextEntry::make('name')
                                    ->label('اسم الفصل')
                                    ->icon(Heroicon::Calendar)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('year')
                                    ->label('السنة')
                                    ->icon(Heroicon::Hashtag)
                                    ->inlineLabel()
                                    ->placeholder('—'),

                                TextEntry::make('season')
                                    ->label('الموسم')
                                    ->icon(Heroicon::Sun)
                                    ->inlineLabel()
                                    ->formatStateUsing(fn (Season $state): string => $state->label()),

                                TextEntry::make('archived_at')
                                    ->label('الحالة')
                                    ->icon(Heroicon::ShieldCheck)
                                    ->inlineLabel()
                                    ->state(fn (Semester $record): string => $record->isArchived()
                                        ? 'مؤرشف منذ '.$record->archived_at->format('Y-m-d')
                                        : 'نشط'),
                            ]),
                    ]),

                // Left column (RTL): dates
                \Filament\Schemas\Components\Grid::make(1)
                    ->columnSpan(1)
                    ->components([
                        Section::make('فترة الفصل الدراسي')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-dates'])
                            ->components([
                                TextEntry::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->icon(Heroicon::CalendarDays)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('—'),

                                TextEntry::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->icon(Heroicon::CalendarDays)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('—'),
                            ]),

                        Section::make('فترة الإضافة والحذف')
                            ->extraAttributes(['class' => 'fsv-card fsv-card-add-drop'])
                            ->components([
                                TextEntry::make('add_drop_start')
                                    ->label('البداية')
                                    ->icon(Heroicon::Clock)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('—'),

                                TextEntry::make('add_drop_end')
                                    ->label('النهاية')
                                    ->icon(Heroicon::Clock)
                                    ->inlineLabel()
                                    ->date()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }

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

                Actions::make([
                    self::openSurveyAction(),
                    self::editSurveyCoursesAction(),
                    self::closeSurveyAction(),
                    self::archiveSemesterAction(),
                ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),
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

                TextColumn::make('archived_at')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (Semester $record): string => $record->isArchived() ? 'مؤرشف' : 'نشط')
                    ->color(fn (Semester $record): string => $record->isArchived() ? 'gray' : 'success')
                    ->icon(fn (Semester $record): string => $record->isArchived() ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open'),

                TextColumn::make('surveys_count')
                    ->label('ردود الاستطلاع')
                    ->counts('surveys')
                    ->badge()
                    ->color('info'),
            ])
            ->recordUrl(fn (Semester $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                self::openSurveyAction(),
                self::editSurveyCoursesAction(),
                self::closeSurveyAction(),
                EditAction::make()
                    ->hidden(fn (Semester $record): bool => $record->isArchived()),
                DeleteAction::make()
                    ->hidden(fn (Semester $record): bool => $record->isArchived()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CourseOfferingsRelationManager::class,
            DecidedApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSemesters::route('/'),
            'create' => CreateSemester::route('/create'),
            'view' => ViewSemester::route('/{record}'),
            'edit' => EditSemester::route('/{record}/edit'),
        ];
    }

    /**
     * Shared schema used by both "open survey" and "edit survey courses" modals
     * — a specialization filter + a checkbox list of active courses. Reused from
     * the table-level actions AND the edit-form button so the popup is identical.
     */
    private static function surveyCourseSelectionSchema(): array
    {
        return [
            Select::make('specialization_filter')
                ->label('تصفية حسب التخصص')
                ->options(fn () => Specialization::orderBy('name')->pluck('name', 'id'))
                ->placeholder('جميع التخصصات')
                ->live()
                ->dehydrated(false),

            CheckboxList::make('course_ids')
                ->label('المقررات')
                ->options(fn (Get $get) => Course::where('is_active', true)
                    ->when($get('specialization_filter'), fn ($q, $id) => $q->whereHas('specializations', fn ($s) => $s->where('specializations.id', $id)))
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($c) => [$c->id => $c->name.' — '.$c->code]))
                ->columns(2)
                ->searchable()
                ->bulkToggleable()
                ->required(),
        ];
    }

    public static function openSurveyAction(): Action
    {
        return Action::make('open_survey')
            ->label('فتح الاستطلاع')
            ->icon(Heroicon::LockOpen)
            ->color('success')
            ->modalHeading('فتح الاستطلاع — اختيار المقررات المتاحة')
            ->modalDescription('اختر المقررات التي ستكون متاحة للطلاب في هذا الفصل.')
            ->modalSubmitActionLabel('فتح الاستطلاع')
            ->fillForm(fn (Semester $record): array => [
                'course_ids' => $record->courses()->pluck('courses.id')->toArray(),
            ])
            ->schema(self::surveyCourseSelectionSchema())
            ->action(function (Semester $record, array $data) {
                $record->courses()->sync($data['course_ids']);
                $record->update(['survey_open' => true]);

                Notification::make()
                    ->title('تم فتح الاستطلاع وتحديد '.count($data['course_ids']).' مقرر')
                    ->success()
                    ->send();
            })
            ->visible(fn (Semester $record) => ! $record->survey_open && ! $record->isArchived());
    }

    public static function editSurveyCoursesAction(): Action
    {
        return Action::make('edit_survey_courses')
            ->label('تعديل الاستطلاع')
            ->icon(Heroicon::Pencil)
            ->color('info')
            ->modalHeading('تعديل المقررات المتاحة للاستطلاع')
            ->modalSubmitActionLabel('حفظ التعديلات')
            ->fillForm(fn (Semester $record): array => [
                'course_ids' => $record->courses()->pluck('courses.id')->toArray(),
            ])
            ->schema(self::surveyCourseSelectionSchema())
            ->action(function (Semester $record, array $data) {
                $record->courses()->sync($data['course_ids']);

                Notification::make()
                    ->title('تم تحديث المقررات المتاحة')
                    ->success()
                    ->send();
            })
            ->visible(fn (Semester $record) => $record->survey_open && ! $record->isArchived());
    }

    public static function closeSurveyAction(): Action
    {
        return Action::make('close_survey')
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
            ->visible(fn (Semester $record) => $record->survey_open && ! $record->isArchived());
    }

    /**
     * Archive the semester permanently via Semester::archiveCascade().
     * After success, lands the admin on the semester's view page (the edit
     * page would 403 once archived).
     */
    public static function archiveSemesterAction(): Action
    {
        return Action::make('archive_semester')
            ->label('إغلاق الفصل')
            ->icon(Heroicon::ArchiveBox)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إغلاق وأرشفة الفصل الدراسي')
            ->modalDescription(fn (Semester $record): string => 'سيتم إغلاق الفصل نهائياً وأرشفته. لن تتمكن من إعادة فتحه أو تعديله أو فتح مقررات جديدة فيه. التسجيلات والدرجات الحالية ستُحفظ كسجل تاريخي. هذا الإجراء لا يمكن التراجع عنه.')
            ->modalSubmitActionLabel('تأكيد الإغلاق والأرشفة')
            ->action(function (Semester $record): void {
                $record->archiveCascade();

                Notification::make()
                    ->title('تمت أرشفة الفصل')
                    ->body('الفصل أصبح للقراءة فقط. لا يمكن إعادة فتحه.')
                    ->success()
                    ->send();
            })
            ->successRedirectUrl(fn (Semester $record): string => self::getUrl('view', ['record' => $record]))
            ->visible(fn (Semester $record): bool => ! $record->isArchived());
    }
}
