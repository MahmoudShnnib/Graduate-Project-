<?php

namespace App\Filament\Student\Pages;

use App\Enums\EnrollmentStatus;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TransferCourse;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GradesPage extends Page
{
    protected string $view = 'filament.student.pages.grades';

    protected static ?string $navigationLabel = 'الدرجات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 3;

    public ?int $selectedSemesterId = null;

    /**
     * The transcript must show exactly the courses that count toward the student's
     * GPA. For a live-record student (Active / OnHold / Inactive) the GPA is computed
     * from Enrolled enrollments only, so withdrawn/flushed courses are hidden — this
     * also covers a student reactivated from suspension back to مستمر. Frozen-record
     * students (Graduated / Transferred / Dismissed) keep their full snapshot.
     */
    private function applyRecordVisibility(Builder $query, Student $student): void
    {
        if (! $student->status->freezesGpa()) {
            $query->where('status', EnrollmentStatus::Enrolled);
        }
    }

    public function getSemesters(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return Semester::whereHas(
            'courseOfferings.enrollments',
            function (Builder $q) use ($student): void {
                $q->where('student_id', $student->id)
                    ->whereHas('grade', fn (Builder $g) => $g->whereNotNull('published_at'));
                $this->applyRecordVisibility($q, $student);
            }
        )
            ->orderByDesc('year')
            ->get();
    }

    public function getGrades(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        $query = StudentEnrollment::where('student_id', $student->id)
            ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
            ->whereHas('grade', fn (Builder $g) => $g->whereNotNull('published_at'));

        $this->applyRecordVisibility($query, $student);

        if ($this->selectedSemesterId) {
            $query->whereHas('courseOffering', fn (Builder $q) => $q->where('semester_id', $this->selectedSemesterId));
        }

        return $query->get();
    }

    public function getTransferCourses(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return TransferCourse::where('student_id', $student->id)
            ->with('course')
            ->get();
    }
}
