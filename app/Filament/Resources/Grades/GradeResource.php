<?php

namespace App\Filament\Resources\Grades;

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\Pages\ListGrades;
use App\Filament\Resources\Grades\Pages\ViewCourseGrades;
use App\Models\CourseOffering;
use App\Models\Grade;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'الدرجات';

    protected static ?string $modelLabel = 'درجة';

    protected static ?string $pluralModelLabel = 'الدرجات';

    protected static string|\UnitEnum|null $navigationGroup = 'شؤون الطلاب';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Grade::where('status', GradeStatus::PendingApproval)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'درجات بانتظار الاعتماد';
    }

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
                    ->withCount([
                        'activeEnrollments as total_students',
                        'activeEnrollments as pending_count' => fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('status', GradeStatus::PendingApproval)),
                        'activeEnrollments as approved_count' => fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('status', GradeStatus::Approved)->whereNull('published_at')),
                        'activeEnrollments as published_count' => fn ($q) => $q->whereHas('grade', fn ($g) => $g->whereNotNull('published_at')),
                    ])
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
                    ->label('الفصل الدراسي'),

                TextColumn::make('faculty.name')
                    ->label('عضو هيئة التدريس')
                    ->placeholder('غير مسند')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('total_students')
                    ->label('إجمالي الطلاب')
                    ->badge()
                    ->color('info'),

                TextColumn::make('pending_count')
                    ->label('بانتظار الاعتماد')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('approved_count')
                    ->label('معتمدة (غير منشورة)')
                    ->badge()
                    ->color('info'),

                TextColumn::make('published_count')
                    ->label('منشورة')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('الفصل')
                    ->relationship('semester', 'name'),
            ])
            ->recordUrl(fn (CourseOffering $record): string => static::getUrl('grades', ['record' => $record]))
            ->emptyStateHeading('لا توجد مواد مفتوحة')
            ->emptyStateDescription('افتح مواد من صفحة الفصول الدراسية أولاً.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrades::route('/'),
            'grades' => ViewCourseGrades::route('/{record}/grades'),
        ];
    }
}
