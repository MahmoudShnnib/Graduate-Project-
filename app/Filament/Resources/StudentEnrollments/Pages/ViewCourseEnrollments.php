<?php

namespace App\Filament\Resources\StudentEnrollments\Pages;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Models\CourseOffering;
use App\Models\StudentEnrollment;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewCourseEnrollments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentEnrollmentResource::class;

    protected string $view = 'filament.resources.student-enrollments.pages.view-course-enrollments';

    public CourseOffering $record;

    public function getTitle(): string
    {
        return 'طلاب مادة: '.$this->record->course->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->course->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentEnrollment::query()
                    ->where('course_offering_id', $this->record->id)
                    ->with(['student.user'])
            )
            ->columns([
                TextColumn::make('student.enrollment_no')
                    ->label('رقم القيد')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('student.user.name')
                    ->label('اسم الطالب')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (EnrollmentStatus $state): string => $state->label())
                    ->color(fn (EnrollmentStatus $state): string => match ($state) {
                        EnrollmentStatus::Enrolled => 'success',
                        EnrollmentStatus::Dropped => 'warning',
                        EnrollmentStatus::Withdrawn => 'danger',
                    }),
            ])
            ->emptyStateHeading('لا يوجد طلاب مسجلون')
            ->emptyStateDescription('لم يُسجَّل أي طالب في هذه المادة بعد.');
    }
}
