<?php

namespace App\Filament\Pages;

use App\Models\Student;
use Filament\Pages\Page;

class ReportsPage extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static ?string $navigationLabel = 'التقارير والنماذج';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 50;

    public ?int $selectedStudentId = null;

    public function getStudentsProperty()
    {
        return Student::with('user', 'specialization')->get()
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
