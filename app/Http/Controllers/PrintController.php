<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CourseOffering;
use App\Models\DiscussionRequest;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\ThesisRegistration;
use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PrintController extends Controller
{
    public function studentLetter(Student $student): Response
    {
        $this->authorizeAdminOnly();

        $student->load(['user.application.semester', 'specialization']);

        $html = view('prints.student_letter', ['student' => $student])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="student-letter-'.$student->enrollment_no.'.pdf"',
        ]);
    }

    public function provisionalGrades(Student $student): Response
    {
        $this->authorizeAdminOnly();

        $student->load(['user.application.semester', 'specialization']);

        $enrollmentRows = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q
                ->whereNotNull('published_at')
                ->whereNotNull('letter')
                ->where('letter', '!=', 'F'))
            ->with(['courseOffering.course', 'grade'])
            ->get()
            ->map(fn ($e) => [
                'code' => $e->courseOffering?->course?->code ?? '—',
                'name' => $e->courseOffering?->course?->name ?? '—',
                'credits' => $e->courseOffering?->course?->credits ?? 3,
                'letter' => $e->grade?->letter ?? '—',
                'total' => $e->grade?->total ?? '—',
            ]);

        $transferRows = $student->transferCourses()
            ->whereNotNull('letter')
            ->where('letter', '!=', 'F')
            ->with('course')
            ->get()
            ->map(fn ($t) => [
                'code' => $t->course?->code ?? '—',
                'name' => $t->course?->name ?? '—',
                'credits' => $t->course?->credits ?? 3,
                'letter' => $t->letter,
                'total' => $t->total ?? '—',
            ]);

        $completedCourses = $enrollmentRows->concat($transferRows)
            ->sortBy('code')
            ->values()
            ->all();

        $failedRows = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->whereNotNull('published_at')->where('letter', 'F'))
            ->with(['courseOffering.course', 'grade'])
            ->get()
            ->map(fn ($e) => [
                'code' => $e->courseOffering?->course?->code ?? '—',
                'name' => $e->courseOffering?->course?->name ?? '—',
                'credits' => $e->courseOffering?->course?->credits ?? 3,
                'letter' => 'F',
                'total' => $e->grade?->total ?? '—',
            ])
            ->sortBy('code')
            ->values()
            ->all();

        $html = view('prints.student_provisional_grades', [
            'student' => $student,
            'completedCourses' => $completedCourses,
            'failedCourses' => $failedRows,
        ])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="provisional-grades-'.$student->enrollment_no.'.pdf"',
        ]);
    }

    public function examAttendance(CourseOffering $courseOffering): Response
    {
        $this->authorizeAdminOrCourseFaculty($courseOffering);

        $courseOffering->load([
            'course.specializations',
            'semester',
            'faculty',
            'activeEnrollments.student.user',
        ]);

        $html = view('prints.exam_attendance', ['offering' => $courseOffering])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="exam-attendance-'.$courseOffering->id.'.pdf"',
        ]);
    }

    public function courseResults(CourseOffering $courseOffering): Response
    {
        $this->authorizeAdminOrCourseFaculty($courseOffering);

        $courseOffering->load([
            'course.specializations',
            'semester',
            'faculty',
            'activeEnrollments.student.user',
            'activeEnrollments.grade',
        ]);

        $html = view('prints.course_results', ['offering' => $courseOffering])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="course-results-'.$courseOffering->id.'.pdf"',
        ]);
    }

    public function examList(Specialization $specialization): Response
    {
        $this->authorizeAdminOnly();

        $applications = Application::query()
            ->where('status', ApplicationStatus::InitiallyApproved)
            ->where('specialization_id', $specialization->id)
            ->orderBy('exam_date')
            ->get();

        $html = view('prints.exam_list', [
            'specialization' => $specialization,
            'applications' => $applications,
            'examDate' => $applications->first()?->exam_date,
        ])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="exam-list-'.$specialization->id.'.pdf"',
        ]);
    }

    public function graduationCertificate(DiscussionRequest $discussionRequest): Response
    {
        $this->authorizeAdminOnly();

        $discussionRequest->load([
            'student.user',
            'student.specialization',
            'student.enrollments.grade',
            'student.enrollments.courseOffering.course',
            'student.transferCourses.course',
            'thesisRegistration',
        ]);

        $student = $discussionRequest->student;

        $lastSemesterDate = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->where('status', 'approved'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
            ->orderByDesc('semesters.end_date')
            ->select('semesters.end_date')
            ->value('semesters.end_date');

        $enrollmentRows = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q
                ->whereNotNull('published_at')
                ->whereNotNull('letter')
                ->where('letter', '!=', 'F'))
            ->with(['courseOffering.course', 'grade'])
            ->get()
            ->map(fn ($e) => [
                'code' => $e->courseOffering?->course?->code ?? '—',
                'name' => $e->courseOffering?->course?->name ?? '—',
                'credits' => $e->courseOffering?->course?->credits ?? 3,
                'letter' => $e->grade?->letter ?? '—',
                'total' => $e->grade?->total ?? '—',
            ]);

        $transferRows = $student->transferCourses()
            ->whereNotNull('letter')
            ->where('letter', '!=', 'F')
            ->with('course')
            ->get()
            ->map(fn ($t) => [
                'code' => $t->course?->code ?? '—',
                'name' => $t->course?->name ?? '—',
                'credits' => $t->course?->credits ?? 3,
                'letter' => $t->letter,
                'total' => $t->total ?? '—',
            ]);

        $completedCourses = $enrollmentRows->concat($transferRows)
            ->sortBy('code')
            ->values()
            ->all();

        $html = view('prints.graduation_certificate', [
            'discussionRequest' => $discussionRequest,
            'student' => $student,
            'completedCourses' => $completedCourses,
            'completionDate' => $lastSemesterDate,
        ])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="graduation-certificate-'.$student->enrollment_no.'.pdf"',
        ]);
    }

    public function thesisMinistryForm(ThesisRegistration $thesisRegistration): Response
    {
        $this->authorizeAdminOnly();

        $thesisRegistration->load([
            'student.user',
            'student.specialization',
            'supervisor',
            'facultySupervisor',
        ]);

        $student = $thesisRegistration->student;

        $registrationSemester = $student->registration_semester;

        $lastSemesterDate = $student->enrollments()
            ->whereHas('grade', fn ($q) => $q->where('status', 'approved'))
            ->join('course_offerings', 'student_enrollments.course_offering_id', '=', 'course_offerings.id')
            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
            ->orderByDesc('semesters.end_date')
            ->select('semesters.end_date', 'semesters.name')
            ->first();

        $html = view('prints.thesis_ministry', [
            'thesis' => $thesisRegistration,
            'student' => $student,
            'registrationSemester' => $registrationSemester,
            'lastSemester' => $lastSemesterDate,
        ])->render();

        $pdf = $this->makeMpdf();
        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="thesis-ministry-'.$student->enrollment_no.'.pdf"',
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
                'amiri' => [
                    'R' => 'amiri-regular.ttf',
                    'B' => 'amiri-bold.ttf',
                    'I' => 'amiri-slanted.ttf',
                    'BI' => 'amiri-boldslanted.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
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
            'default_font_size' => 16,
        ]);
    }

    private function authorizeAdminOnly(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, ['super_admin', 'manager_assistant'], true),
            403
        );
    }

    private function authorizeAdminOrCourseFaculty(CourseOffering $offering): void
    {
        $user = auth()->user();
        $isAdmin = in_array($user?->role, ['super_admin', 'manager_assistant'], true);
        $isFaculty = $user?->role === 'faculty' && $offering->faculty_id === $user->id;
        abort_unless($isAdmin || $isFaculty, 403);
    }
}
