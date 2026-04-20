<?php

namespace App\Filament\Resources\Students\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ViewStudent extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.resources.students.pages.view-student';

    public ?int $selectedSemesterId = null;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getStudentSemesters(): Collection
    {
        return Semester::query()
            ->whereHas('courseOfferings.enrollments', function ($query) {
                $query->where('student_id', $this->record->id)
                    ->where('status', EnrollmentStatus::Enrolled);
            })
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();
    }

    public function updatedSelectedSemesterId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = StudentEnrollment::query()
                    ->where('student_id', $this->record->id)
                    ->active()
                    ->with(['courseOffering.course', 'grade']);

                if ($this->selectedSemesterId) {
                    $query->whereHas('courseOffering', fn (Builder $q) => $q->where('semester_id', $this->selectedSemesterId));
                } else {
                    $query->whereRaw('0 = 1');
                }

                return $query;
            })
            ->columns([
                TextColumn::make('courseOffering.course.name')
                    ->label('المادة')
                    ->searchable(),

                TextColumn::make('courseOffering.course.code')
                    ->label('رمز المادة')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('courseOffering.course.credits')
                    ->label('الساعات')
                    ->alignCenter(),

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

                TextColumn::make('grade.points')
                    ->label('النقاط')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('grade.status')
                    ->label('الحالة')
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof GradeStatus ? $state->label() : 'لم تُدخل')
                    ->color(fn (mixed $state): string => match (true) {
                        $state === GradeStatus::Approved => 'success',
                        $state === GradeStatus::PendingApproval => 'warning',
                        $state === GradeStatus::Draft => 'info',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(fn (): string => $this->selectedSemesterId
                ? 'لا توجد مواد مسجلة في هذا الفصل.'
                : 'اختر فصلاً دراسياً لعرض الدرجات.')
            ->emptyStateDescription(null)
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
