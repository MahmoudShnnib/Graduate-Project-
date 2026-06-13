<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PrintCourseStudentsController extends Controller
{
    public function __invoke(CourseOffering $courseOffering): Response
    {
        abort_unless(
            in_array(auth()->user()?->role, ['super_admin', 'manager_assistant'], true),
            403
        );

        $courseOffering->load([
            'course.specializations',
            'semester',
            'faculty',
            'activeEnrollments.student.user',
            'activeEnrollments.student.specialization',
        ]);

        $students = $courseOffering->activeEnrollments
            ->sortBy([
                ['student.specialization.name', 'asc'],
                ['student.user.name', 'asc'],
            ])
            ->values();

        $html = view('prints.course-students', [
            'courseOffering' => $courseOffering,
            'students' => $students,
        ])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="course-roster-'.$courseOffering->id.'.pdf"',
        ]);
    }

    private function makeMpdf(): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_left' => 25.4,
            'margin_right' => 25.4,
            'margin_top' => 25.4,
            'margin_bottom' => 25.4,
            'margin_header' => 12.7,
            'margin_footer' => 12.7,
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'useSubstitutions' => true,
            'backupSubsFont' => ['dejavusans'],
            'fontDir' => array_merge($fontDirs, [resource_path('fonts/print')]),
            'fontdata' => $fontData + [
                'noto-naskh' => [
                    'R' => 'NotoNaskhArabic-Regular.ttf',
                    'B' => 'NotoNaskhArabic-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
                'markazi' => [
                    'R' => 'MarkaziText-VF.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'noto-naskh',
            'default_font_size' => 14,
        ]);
    }
}
