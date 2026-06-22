<?php

namespace App\Filament\Resources\Faculty;

use App\Enums\GradeStatus;
use App\Filament\Resources\Faculty\Pages\ListMyCourses;
use App\Filament\Resources\Faculty\Pages\ViewMyCourse;
use App\Models\CourseOffering;
use App\Models\Semester;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyCourseResource extends Resource
{
    protected static ?string $model = CourseOffering::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'موادي';

    protected static ?string $modelLabel = 'مادة';

    protected static ?string $pluralModelLabel = 'موادي';

    protected static ?string $slug = 'my-courses';

    protected static ?string $recordTitleAttribute = 'course.name';

    protected static string|\UnitEnum|null $navigationGroup = 'هيئة التدريس';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasRole('faculty')
            && auth()->user()->can('ViewAny:CourseOffering');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('faculty')
            && auth()->user()->can('ViewAny:CourseOffering');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('faculty_id', auth()->id())
            ->with(['course', 'semester'])
            ->withCount([
                'activeEnrollments as enrollments_count',
                'activeEnrollments as grade_approved_count' => fn (Builder $query) => $query->whereHas('grade', fn (Builder $g) => $g->where('status', GradeStatus::Approved)),
                'activeEnrollments as grade_pending_count' => fn (Builder $query) => $query->whereHas('grade', fn (Builder $g) => $g->where('status', GradeStatus::PendingApproval)),
                'activeEnrollments as grade_draft_count' => fn (Builder $query) => $query->whereHas('grade', fn (Builder $g) => $g->where('status', GradeStatus::Draft)),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (CourseOffering $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('course.name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('semester.name')
                    ->label('الفصل الدراسي')
                    ->sortable(),

                TextColumn::make('enrollments_count')
                    ->label('عدد الطلاب')
                    ->badge()
                    ->color('info'),

                TextColumn::make('grades_status')
                    ->label('حالة الدرجات')
                    ->getStateUsing(function (CourseOffering $record): string {
                        $total = (int) $record->enrollments_count;

                        if ($total === 0) {
                            return 'لا يوجد طلاب';
                        }

                        $approved = (int) $record->grade_approved_count;
                        $pending = (int) $record->grade_pending_count;
                        $draft = (int) $record->grade_draft_count;

                        if ($approved === $total) {
                            return 'معتمدة بالكامل';
                        }

                        if ($pending > 0) {
                            return 'بانتظار الاعتماد';
                        }

                        if ($draft > 0) {
                            return 'مسودة';
                        }

                        return 'لم تُدخل بعد';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'معتمدة بالكامل' => 'success',
                        'بانتظار الاعتماد' => 'warning',
                        'مسودة' => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('is_open')
                    ->label('مفتوح للتسجيل')
                    ->boolean()
                    ->extraAttributes(fn (CourseOffering $record): array => [
                        'aria-label' => $record->is_open ? 'مفتوح للتسجيل' : 'مغلق للتسجيل',
                    ]),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('الفصل الدراسي')
                    ->relationship('semester', 'name')
                    ->default(Semester::activeId()),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyCourses::route('/'),
            'view' => ViewMyCourse::route('/{record}'),
        ];
    }
}
