<?php

namespace App\Filament\Resources\CourseOfferings;

use App\Filament\Resources\CourseOfferings\Pages\CreateCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EditCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EnrollStudents;
use App\Filament\Resources\CourseOfferings\Pages\ListCourseOfferings;
use App\Models\CourseOffering;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                    ->relationship(
                        name: 'course',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, Get $get, ?Model $record): Builder {
                            $semesterId = $get('semester_id');
                            if (! $semesterId) {
                                return $query;
                            }
                            $takenCourseIds = CourseOffering::where('semester_id', $semesterId)
                                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                ->pluck('course_id');

                            return $query->whereNotIn('id', $takenCourseIds);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                Select::make('semester_id')
                    ->label('الفصل الدراسي')
                    ->relationship('semester', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->default(fn (): ?int => Semester::where('is_active', true)->value('id'))
                    ->disabled(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(true)
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $courseId = $get('course_id');
                            if (! $courseId || ! $value) {
                                return;
                            }
                            $exists = CourseOffering::where('course_id', $courseId)
                                ->where('semester_id', $value)
                                ->where('is_open', true)
                                ->exists();
                            if ($exists) {
                                $fail('يوجد مقرر مفتوح بالفعل لهذا الفصل الدراسي.');
                            }
                        },
                    ]),

                Select::make('faculty_id')
                    ->label('عضو هيئة التدريس')
                    ->relationship('faculty', 'name', fn ($query) => $query->where('role', 'faculty'))
                    ->searchable()
                    ->preload(),

                Toggle::make('is_open')
                    ->label('مفتوح للتسجيل')
                    ->default(true),
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
                        ->where('is_processed', false)
                        ->count()
                    )
                    ->badge()
                    ->color('warning'),

                TextColumn::make('active_enrollments_count')
                    ->label('مسجّلون')
                    ->counts('activeEnrollments')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_open')
                    ->label('مفتوح')
                    ->boolean(),
            ])
            ->modifyQueryUsing(function ($query) {
                $activeSemesterId = Semester::where('is_active', true)->value('id');

                return $query
                    ->where('is_open', true)
                    ->where('semester_id', $activeSemesterId ?: 0);
            })
            ->recordUrl(fn (CourseOffering $record): string => static::getUrl('enroll', ['record' => $record]))
            ->recordActions([
                EditAction::make(),
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
