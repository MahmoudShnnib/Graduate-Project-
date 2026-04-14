<?php

namespace App\Filament\Resources\StudentEnrollments;

use App\Filament\Resources\StudentEnrollments\Pages\ListStudentEnrollments;
use App\Filament\Resources\StudentEnrollments\Pages\ViewCourseEnrollments;
use App\Models\CourseOffering;
use App\Models\StudentEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'التسجيلات';

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                CourseOffering::query()
                    ->with(['course', 'semester', 'faculty'])
                    ->withCount('enrollments')
            )
            ->columns([
                TextColumn::make('course.name')
                    ->label('المقرر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('semester.name')
                    ->label('الفصل الدراسي')
                    ->sortable(),

                TextColumn::make('faculty.name')
                    ->label('عضو هيئة التدريس')
                    ->placeholder('غير مسند')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('enrollments_count')
                    ->label('عدد الطلاب المسجلين')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('الفصل')
                    ->relationship('semester', 'name'),
            ])
            ->recordUrl(fn (CourseOffering $record): string => static::getUrl('students', ['record' => $record]))
            ->emptyStateHeading('لا توجد مواد مفتوحة')
            ->emptyStateDescription('افتح مواد من صفحة الفصول الدراسية أولاً.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentEnrollments::route('/'),
            'students' => ViewCourseEnrollments::route('/{record}/students'),
        ];
    }
}
