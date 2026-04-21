<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AddDropRequests\AddDropRequestResource;
use App\Models\AddDropRequest;
use App\Models\Semester;
use App\Models\Student;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Per-student drill-in for add/drop requests — reached from the
 * {@see \App\Filament\Resources\AddDropRequests\Pages\ListAddDropRequests} grouped index.
 *
 * Reuses {@see AddDropRequestResource::table()} as the single source of truth for
 * columns and approve/reject actions, scoped to this student + semester.
 */
class StudentAddDropView extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.student-add-drop-view';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static bool $shouldRegisterNavigation = false;

    public ?Student $student = null;

    public ?Semester $semester = null;

    public function mount(Student $student, Semester $semester): void
    {
        $this->student = $student->load(['user:id,name', 'specialization:id,name']);
        $this->semester = $semester;
    }

    public function getTitle(): string
    {
        if (! $this->student || ! $this->semester) {
            return 'طلبات الإضافة/الحذف';
        }

        return 'طلبات الإضافة/الحذف — '.$this->student->user?->name.' · '.$this->semester->name;
    }

    /**
     * Delegate to the Resource's canonical table definition, then provide
     * the base query scoped to this student + semester. Keeps the
     * approve/reject logic + exception catches in one place
     * (AddDropRequestResource::table).
     *
     * Note: calling a Resource's `table()` from a non-Resource Page leaves
     * the table without a base query (the Resource's getEloquentQuery()
     * isn't wired). We must supply `->query(...)` explicitly, otherwise
     * Filament throws "Table must have a [query()], [relationship()], or [records()]".
     */
    public function table(Table $table): Table
    {
        $studentId = $this->student->id;
        $semesterId = $this->semester->id;

        return AddDropRequestResource::table($table)
            ->query(fn () => AddDropRequest::query()
                ->where('student_id', $studentId)
                ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $semesterId)));
    }

    /**
     * Quick-access stats used by the detail Blade.
     *
     * @return array{total: int, pending: int, approved: int, rejected: int, completed: bool}
     */
    public function getStats(): array
    {
        $requests = $this->student->addDropRequests()
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $this->semester->id))
            ->get();

        $counts = [
            'total' => $requests->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'approved' => $requests->where('status', 'approved')->count(),
            'rejected' => $requests->where('status', 'rejected')->count(),
        ];

        $counts['completed'] = $counts['total'] > 0 && $counts['pending'] === 0;

        return $counts;
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/student-add-drop/{student}/{semester}';
    }
}
