<?php

namespace App\Services;

use App\Models\Student;
use App\Models\SuspensionRequest;
use App\Models\ThesisRegistration;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateTranscript(Student $student): string
    {
        $student->load(['user', 'specialization', 'enrollments.courseOffering.course', 'enrollments.grade']);

        $pdf = Pdf::loadView('pdfs.transcript', compact('student'));
        $path = "pdfs/transcript_{$student->id}_".now()->timestamp.'.pdf';

        \Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function generateClearanceForm(Student $student): string
    {
        $student->load(['user', 'specialization', 'clearanceRequest']);

        $pdf = Pdf::loadView('pdfs.clearance_form', compact('student'));
        $path = "pdfs/clearance_{$student->id}_".now()->timestamp.'.pdf';

        \Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function generateSuspensionForm(SuspensionRequest $request): string
    {
        $request->load(['student.user', 'semester']);

        $pdf = Pdf::loadView('pdfs.suspension_form', compact('request'));
        $path = "pdfs/suspension_{$request->id}_".now()->timestamp.'.pdf';

        \Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function generateThesisForm(ThesisRegistration $registration): string
    {
        $registration->load(['student.user', 'student.specialization']);

        $pdf = Pdf::loadView('pdfs.thesis_form', compact('registration'));
        $path = "pdfs/thesis_{$registration->id}_".now()->timestamp.'.pdf';

        \Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
