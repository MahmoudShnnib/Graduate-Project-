<?php

namespace App\Filament\Pages;

use App\Models\Student;
use Filament\Pages\Page;

class ReportsPage extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static ?string $navigationLabel = 'التقارير والنماذج';

    protected static ?string $title = 'التقارير والنماذج';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الوصول والنظام';

    protected static ?int $navigationSort = 3;

    public ?int $selectedStudentId = null;

    public string $studentSearch = '';

    /**
     * Lazily resolve at most 50 matching students instead of hydrating every Student
     * row + relations on each render. Scales to a large student body without growing
     * the page's memory footprint.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        return Student::query()
            ->with('user:id,name')
            ->when($this->studentSearch !== '', function ($query) {
                $term = '%'.$this->studentSearch.'%';

                $query->where('enrollment_no', 'like', $term)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term));
            })
            ->orderBy('enrollment_no')
            ->limit(50)
            ->get(['id', 'user_id', 'enrollment_no'])
            ->mapWithKeys(fn (Student $s) => [$s->id => "{$s->enrollment_no} - {$s->user?->name}"]);
    }

    public function downloadTranscript(): void
    {
        if (! $this->selectedStudentId) {
            return;
        }

        $student = Student::findOrFail($this->selectedStudentId);
        $pdfService = app(\App\Services\PdfService::class);
        $path = $pdfService->generateTranscript($student);

        redirect(asset('storage/'.$path));
    }

    public function downloadClearanceForm(): void
    {
        if (! $this->selectedStudentId) {
            return;
        }

        $student = Student::findOrFail($this->selectedStudentId);
        $pdfService = app(\App\Services\PdfService::class);
        $path = $pdfService->generateClearanceForm($student);

        redirect(asset('storage/'.$path));
    }
}
