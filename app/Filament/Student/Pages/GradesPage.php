<?php

namespace App\Filament\Student\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
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

    private ?int $activeSemesterId = null;

    private bool $activeSemesterResolved = false;

    private function activeSemesterId(): ?int
    {
        if (! $this->activeSemesterResolved) {
            $this->activeSemesterId = Semester::active()?->id;
            $this->activeSemesterResolved = true;
        }

        return $this->activeSemesterId;
    }

    /**
     * For a suspended (قيده موقوف) or dropped-out (منقطع) student, hide the
     * current-semester enrollments that were flushed (withdrawn AND removed from
     * the GPA) from the transcript, while keeping prior semesters intact. Other
     * statuses — including Graduated, whose GPA is frozen WITH the term — are
     * left untouched.
     */
    private function excludeSuspendedCurrent(Builder $query, Student $student): void
    {
        $activeSemesterId = $this->activeSemesterId();

        $isSuspended = in_array($student->status, [StudentStatus::OnHold, StudentStatus::Inactive], true);

        if ($activeSemesterId === null || ! $isSuspended) {
            return;
        }

        $query->where(function (Builder $q) use ($activeSemesterId): void {
            $q->where('status', EnrollmentStatus::Enrolled)
                ->orWhereHas('courseOffering', fn (Builder $co) => $co->where('semester_id', '!=', $activeSemesterId));
        });
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
                $this->excludeSuspendedCurrent($q, $student);
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

        $this->excludeSuspendedCurrent($query, $student);

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
