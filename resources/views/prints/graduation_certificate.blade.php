@php
    /** @var \App\Models\Student $student */
    /** @var \App\Models\DiscussionRequest $discussionRequest */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName   = \App\Models\Setting::get('director_name');
    $deanName       = \App\Models\Setting::get('dean_name');
    $directorNameEn = \App\Models\Setting::get('director_name_en') ?: $directorName;
    $deanNameEn     = \App\Models\Setting::get('dean_name_en') ?: $deanName;

    $studentName    = $student->user?->name ?? '—';
    $enrollmentNo   = $student->enrollment_no ?? '—';
    $specialization = $student->specialization?->name ?? '—';
    $gpa            = $student->gpa !== null ? number_format((float) $student->gpa, 2) : '—';

    $dateFormatted = $completionDate
        ? \Carbon\Carbon::parse($completionDate)->format('Y/m/d')
        : '__/__/____';

    $enrollmentNoHtml = preg_replace(
        '/(\d+)/',
        '<span style="font-family:markazi;font-size:22pt;font-weight:normal;">$1</span>',
        e($enrollmentNo)
    );
    $gpaHtml = preg_replace(
        '/(\d[\d,.]*)/',
        '<span style="font-family:markazi;font-size:16pt;font-weight:normal;">$1</span>',
        e($gpa)
    );
    $dateHtml = preg_replace(
        '/(\d+)/',
        '<span style="font-family:markazi;font-size:16pt;font-weight:normal;">$1</span>',
        e($dateFormatted)
    );

    $thesisTitle   = $discussionRequest->thesisRegistration?->title ?? null;
    $thesisTitleEn = $discussionRequest->thesisRegistration?->title_en ?? null;

    $totalCredits = array_sum(array_column($completedCourses, 'credits'));
    $courseCount  = count($completedCourses);
    $numericScores = collect($completedCourses)
        ->map(fn ($c) => is_numeric($c['total']) ? (float) $c['total'] : null)
        ->filter(fn ($v) => $v !== null);
    $totalScore   = $numericScores->isNotEmpty() ? number_format($numericScores->avg(), 1) : '—';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    /* ========== PAGE 1 : Arabic letter ========== */
    body {
        font-family: 'noto-naskh', serif;
        font-size: 15pt;
        line-height: 1.6;
        color: #000;
        direction: rtl;
        text-align: center;
    }
    p { margin: 0 0 10pt 0; text-align: center; }

    .page-1-wrapper { margin-top: 50mm; }

    .cert-title-box {
        display: table;
        margin: 0 auto 30pt;
        border: 2pt solid #000;
        padding: 8pt 30pt;
        background: #d9d9d9;
    }
    .cert-title-box span { font-size: 22pt; font-weight: bold; }

    .cert-name { font-size: 20pt; font-weight: bold; margin: 6pt 0; }

    .sigs-table { width: 100%; border-collapse: collapse; margin-top: 40pt; }
    .sig-cell-r {
        width: 50%; text-align: right; direction: rtl;
        font-size: 12pt; line-height: 1.8; vertical-align: top;
    }
    .sig-cell-l {
        width: 50%; text-align: left; direction: rtl;
        font-size: 12pt; line-height: 1.8; vertical-align: top;
    }
    .sig-name { white-space: nowrap; }

    /* ========== PAGE 2 : English transcript ========== */
    .transcript-page {
        page-break-before: always;
        direction: ltr;
        text-align: left;
        font-family: 'noto-naskh', serif;
    }
    .transcript-note {
        text-align: right;
        font-size: 10pt;
        direction: rtl;
        margin-bottom: 16pt;
        color: #444;
    }
    .transcript-heading {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        margin: 10pt 0 16pt;
        text-decoration: underline;
        direction: ltr;
        font-family: 'dejavuserif';
    }
    .tr-info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        font-size: 11pt;
        direction: ltr;
    }
    .tr-info-table th, .tr-info-table td {
        border: 1pt solid #000;
        padding: 4pt 6pt;
        text-align: left;
    }
    .tr-info-table th { font-weight: bold; background: #f0f0f0; text-align: center; font-family: 'dejavuserif'; }
    .tr-courses-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        font-size: 11pt;
        direction: ltr;
    }
    .tr-courses-table th, .tr-courses-table td {
        border: 1pt solid #000;
        padding: 7pt 6pt;
        text-align: center;
    }
    .tr-courses-table th { font-weight: bold; background: #f0f0f0; font-family: 'dejavuserif'; }
    .tr-courses-table .td-name { text-align: left; direction: rtl; font-family: 'dejavuserif'; }
    .tr-courses-table .td-en { font-family: 'dejavuserif'; }
    .tr-summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        font-size: 11pt;
        direction: ltr;
    }
    .tr-summary-table td {
        border: 1pt solid #000;
        padding: 4pt 6pt;
        font-weight: bold;
        text-align: center;
        font-family: 'dejavuserif';
    }
    .tr-grade-table {
        border-collapse: collapse;
        font-size: 9pt;
        direction: ltr;
        margin-top: 3pt;
        margin-left: 0;
        margin-right: auto;
    }
    .tr-grade-table th, .tr-grade-table td {
        border: 1pt solid #000;
        padding: 3pt 6pt;
        text-align: center;
        white-space: nowrap;
    }
    .tr-grade-table th { background: #f0f0f0; font-weight: bold; font-family: 'dejavuserif'; }
    .tr-grade-table td { font-family: 'dejavuserif'; }
    .tr-note { font-size: 11pt; font-weight: bold; margin: 8pt 0; direction: ltr; font-family: 'noto-naskh'; }
    .tr-grade-label { font-size: 7pt; font-weight: bold; margin: 28pt 0 3pt; direction: ltr; text-align: left; font-family: 'dejavusans'; }
</style>
</head>
<body>

{{-- ===== PAGE 1 : Arabic Graduation Letter ===== --}}
<div class="page-1-wrapper">

    <div class="cert-title-box">
        <span>إفـادة تخـرج</span>
    </div>

    <p>تفيد {{ $collegeName }} بأن الطالب</p>

    <p class="cert-name">{{ $studentName }}</p>

    <p>رقم قيده : {!! $enrollmentNoHtml !!}</p>

    <p>أتم بنجاح جميع المتطلبات الدراسية المقررة لنيل درجة " الماجستير في الهندسة الإلكترونية تخصص <strong>{{ $specialization }}</strong> "</p>

    <p>وذلك بتاريخ {!! $dateHtml !!} م &nbsp; وبمعدل تراكمي &nbsp;( {!! $gpaHtml !!} &nbsp; من &nbsp; <span style="font-family:markazi;font-size:16pt;font-weight:normal;">4</span> )</p>

    <p>وقد اعطيت له هذه الافادة بناءً على طلبه لاستعمالها فيما يسمح به القانون</p>

</div>

<table class="sigs-table" dir="ltr">
    <tr>
        <td class="sig-cell-l">
            <div class="sig-name">{{ $deanName }}</div>
            <div>عميد الكلية</div>
        </td>
        <td class="sig-cell-r">
            <div class="sig-name">{{ $directorName }}</div>
            <div>مدير {{ $officeName }}</div>
        </td>
    </tr>
</table>

{{-- ===== PAGE 2 : English Student Transcript ===== --}}
<div class="transcript-page">

    <div class="transcript-note">*أي كشط أو تعديل يلغيها ولا يعتد إلا بالاصل</div>

    <div class="transcript-heading">Student Transcript</div>

    <table class="tr-info-table">
        <tr>
            <th style="width: 33%;">Name</th>
            <th style="width: 22%;">Seat No.</th>
            <th style="width: 45%;">Department</th>
        </tr>
        <tr>
            <td style="direction: rtl; text-align: center;">{{ $studentName }}</td>
            <td style="font-family: 'markazi'; font-size: 13pt; text-align: center;">{{ $enrollmentNo }}</td>
            <td style="direction: rtl; text-align: center;">{{ $specialization }}</td>
        </tr>
    </table>

    <table class="tr-courses-table">
        <thead>
            <tr>
                <th style="width: 30px; font-family: dejavuserif;">No.</th>
                <th style="font-family: dejavuserif;">Course Title</th>
                <th style="width: 60px; font-family: dejavuserif;">Code</th>
                <th style="width: 45px; font-family: dejavuserif;">Credit</th>
                <th style="width: 45px; font-family: dejavuserif;">Grade</th>
                <th style="width: 45px; font-family: dejavuserif;">Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($completedCourses as $i => $row)
                <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                    <td class="td-en">{{ $i + 1 }}</td>
                    <td class="td-name">{{ $row['name'] }}</td>
                    <td class="td-en">{{ $row['code'] }}</td>
                    <td class="td-en">{{ $row['credits'] }}</td>
                    <td class="td-en">{{ $row['letter'] }}</td>
                    <td class="td-en">{{ $row['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="tr-summary-table">
        <tr>
            <td>Total Score</td>
            <td>Number of Courses</td>
            <td>Accumulative G.P.A</td>
            <td>Total of Credits</td>
        </tr>
        <tr>
            <td style="font-family: 'markazi'; font-size: 13pt;">{{ $totalScore }}</td>
            <td>{{ $courseCount }}</td>
            <td style="font-family: 'markazi'; font-size: 14pt;">{{ $gpa }}</td>
            <td>{{ $totalCredits }}</td>
        </tr>
    </table>

    @if ($thesisTitle)
        <p class="tr-note" style="font-family: dejavusans;">The final thesis was successfully completed in the semester that was on.</p>
        <p class="tr-note">The final thesis topic is &ldquo;{{ $thesisTitle }}&rdquo;</p>
    @endif

    <p class="tr-grade-label">Official Grading system for graduate Studies</p>
    <table class="tr-grade-table">
        <tr>
            <th>A</th><th>B+</th><th>B</th><th>C+</th><th>C</th><th>D+</th><th>D</th><th>F</th>
        </tr>
        <tr>
            <td>4</td><td>3.5</td><td>3</td><td>2.5</td><td>2</td><td>1.5</td><td>1</td><td>0</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-top:30pt; font-family:dejavuserif; font-size:11pt; line-height:1.6;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:top;">
                <div>{{ $deanNameEn }}</div>
                <div>Dean of College</div>
            </td>
            <td style="width:50%; text-align:right; vertical-align:top;">
                <div>{{ $directorNameEn }}</div>
                <div>Director of Graduate Studies</div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
