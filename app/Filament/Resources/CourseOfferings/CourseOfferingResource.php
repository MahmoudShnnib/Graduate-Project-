<?php

namespace App\Filament\Resources\CourseOfferings;

use App\Filament\Resources\CourseOfferings\Pages\CreateCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EditCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EnrollStudents;
use App\Filament\Resources\CourseOfferings\Pages\ListCourseOfferings;
use App\Models\CourseOffering;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseOfferingResource extends Resource
{
    protected static ?string $model = CourseOffering::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'عروض المقررات';

    protected static ?string $modelLabel = 'عرض مقرر';

    protected static ?string $pluralModelLabel = 'عروض المقررات';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->label('المقرر')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('semester_id')
                    ->label('الفصل الدراسي')
                    ->relationship('semester', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('faculty_id')
                    ->label('عضو هيئة التدريس')
                    ->relationship('faculty', 'name', fn ($query) => $query->where('role', 'faculty'))
                    ->searchable()
                    ->preload(),

                Toggle::make('is_open')
                    ->label('مفتوح للتسجيل')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.name')
                    ->label('المقرر')
                    ->searchable(),

                TextColumn::make('semester.name')
                    ->label('الفصل'),

                TextColumn::make('faculty.name')
                    ->label('الأستاذ')
                    ->placeholder('غير مسند')
                    ->badge()
                    ->color('info'),

                TextColumn::make('survey_count')
                    ->label('طلبوا المادة')
                    ->getStateUsing(fn (CourseOffering $record): int => \App\Models\CourseSurvey::where('course_id', $record->course_id)
                        ->where('semester_id', $record->semester_id)
                        ->count()
                    )
                    ->badge()
                    ->color('warning'),

                TextColumn::make('active_enrollments_count')
                    ->label('مسجّلون فعلاً')
                    ->counts('activeEnrollments')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_open')
                    ->label('مفتوح')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('الفصل')
                    ->relationship('semester', 'name'),
            ])
            ->recordUrl(fn (CourseOffering $record): string => static::getUrl('enroll', ['record' => $record]))
            ->recordActions([
                Action::make('toggle_open')
                    ->label(fn (CourseOffering $record) => $record->is_open ? 'إغلاق' : 'فتح')
                    ->icon(fn (CourseOffering $record) => $record->is_open ? Heroicon::LockClosed : Heroicon::LockOpen)
                    ->color(fn (CourseOffering $record) => $record->is_open ? 'warning' : 'success')
                    ->action(function (CourseOffering $record) {
                        $record->update(['is_open' => ! $record->is_open]);
                        Notification::make()
                            ->title($record->is_open ? 'تم فتح المقرر' : 'تم إغلاق المقرر')
                            ->success()
                            ->send();
                    }),

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
            'index' => ListCourseOfferings::route('/'),
            'create' => CreateCourseOffering::route('/create'),
            'edit' => EditCourseOffering::route('/{record}/edit'),
            'enroll' => EnrollStudents::route('/{record}/enroll'),
        ];
    }
}
