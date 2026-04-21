<?php

namespace App\Filament\Resources\Students\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Filament\Resources\Students\StudentResource;
use App\Models\StudentEnrollment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewStudent extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.resources.students.pages.view-student';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StudentEnrollment::query()
                ->where('student_id', $this->record->id)
                ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
                ->reorder()
                ->orderByRaw(
                    'CASE WHEN student_enrollments.status = ? '
                    .'AND NOT EXISTS (SELECT 1 FROM grades '
                    .'WHERE grades.enrollment_id = student_enrollments.id '
                    .'AND grades.status = ?) THEN 0 ELSE 1 END ASC',
                    [EnrollmentStatus::Enrolled->value, GradeStatus::Approved->value]
                )
                ->orderByDesc('course_offering_id')
            )
            ->columns([
                TextColumn::make('courseOffering.course.name')
                    ->label('المادة')
                    ->searchable(),

                TextColumn::make('courseOffering.course.code')
                    ->label('رمز المادة')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('courseOffering.semester.name')
                    ->label('الفصل الدراسي')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('grade.coursework_grade')
                    ->label('أعمال الفصل')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('grade.final_grade')
                    ->label('النهائي')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('grade.total')
                    ->label('المجموع')
                    ->alignCenter()
                    ->weight('bold')
                    ->placeholder('—'),

                TextColumn::make('grade.letter')
                    ->label('التقدير')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A', 'B+', 'B', 'C+', 'C' => 'success',
                        'D+', 'D' => 'warning',
                        'F' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),

                TextColumn::make('enrollment_status_chip')
                    ->label('الحالة')
                    ->alignCenter()
                    ->badge()
                    ->state(fn (StudentEnrollment $record): string => self::computeStatusLabel($record))
                    ->color(fn (StudentEnrollment $record): string => self::computeStatusColor($record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد مقررات مسجلة لهذا الطالب.')
            ->emptyStateDescription(null)
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    private static function computeStatusLabel(StudentEnrollment $record): string
    {
        if ($record->status === EnrollmentStatus::Dropped) {
            return 'محذوف';
        }

        if ($record->status === EnrollmentStatus::Withdrawn) {
            return 'منسحب';
        }

        $approved = $record->grade && $record->grade->status === GradeStatus::Approved;

        if (! $approved) {
            return 'يدرس حالياً';
        }

        return $record->grade->letter === 'F' ? 'راسب' : 'ناجح';
    }

    private static function computeStatusColor(StudentEnrollment $record): string
    {
        if ($record->status === EnrollmentStatus::Dropped) {
            return 'gray';
        }

        if ($record->status === EnrollmentStatus::Withdrawn) {
            return 'gray';
        }

        $approved = $record->grade && $record->grade->status === GradeStatus::Approved;

        if (! $approved) {
            return 'warning';
        }

        return $record->grade->letter === 'F' ? 'danger' : 'success';
    }
}
