@php
    /** @var \App\Models\Student $student */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');

    $studentName     = $student->user?->name ?? '—';
    $enrollmentNo    = $student->enrollment_no ?? '—';
    $specialization  = $student->specialization?->name ?? '—';
    $regSemester     = $student->registration_semester?->name ?? '—';

    $today = now()->format('d / m / Y');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 14pt;
        line-height: 1.4;
        color: #000;
        direction: rtl;
        text-align: right;
    }
    p { margin: 0; }

    .header { text-align: center; margin-bottom: 4pt; }
    .header-college { font-size: 18pt; font-weight: bold; margin-bottom: 2pt; }
    .header-office  { font-size: 16pt; font-weight: bold; margin-bottom: 6pt; }

    .date-line { text-align: right; font-size: 12pt; margin-bottom: 6pt; }

    .doc-title {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        text-decoration: underline;
        font-style: italic;
        margin: 10pt 0 16pt;
    }

    .student-info { text-align: right; font-size: 14pt; margin-bottom: 6pt; line-height: 1.6; }

    .double-rule { border-top: 3pt double #000; margin: 8pt 0; }

    .section-label { font-size: 14pt; text-align: right; margin-bottom: 4pt; }

    .courses-table { width: 100%; border-collapse: collapse; margin-bottom: 4pt; font-size: 11pt; direction: ltr; }
    .courses-table th, .courses-table td { border: 1pt solid #000; padding: 7pt 6pt; text-align: center; }
    .courses-table th { font-weight: bold; background: #f0f0f0; font-family: 'dejavuserif'; }
    .courses-table .td-name { text-align: left; direction: rtl; font-family: 'dejavuserif'; }
    .courses-table .td-en { font-family: 'dejavuserif'; }

    .thesis-note { text-align: right; font-size: 13pt; margin: 4pt 0; }

    .signature {
        margin-top: 20pt;
        float: left;
        clear: both;
        width: 55mm;
        text-align: right;
        direction: rtl;
        font-size: 12pt;
        line-height: 1.6;
        overflow: visible;
    }
    .signature > div { direction: rtl; text-align: right; white-space: nowrap; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-office">{{ $officeName }}</div>
</div>

<div class="date-line">
    التاريخ:
    {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:14pt;font-weight:normal;">$1</span>', e($today)) !!}
    م
</div>

<div class="doc-title">كشف درجات مؤقت</div>

<div class="student-info">
    <div>الإسم : {{ $studentName }}</div>
    <div>
        رقم القيد:
        {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:16pt;font-weight:normal;">$1</span>', e($enrollmentNo)) !!}
    </div>
    <div>التخصص: {{ $specialization }}</div>
    <div>
        تاريخ الإلتحاق :
        {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:14pt;font-weight:normal;">$1</span>', e($regSemester)) !!}
        م
    </div>
</div>

<div class="double-rule"></div>

<div class="section-label">المواد المنجزة:</div>

<table class="courses-table">
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
        @forelse ($completedCourses as $i => $row)
            <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                <td class="td-en">{{ $i + 1 }}</td>
                <td class="td-name">{{ $row['name'] }}</td>
                <td class="td-en">{{ $row['code'] }}</td>
                <td class="td-en">{{ $row['credits'] }}</td>
                <td class="td-en">{{ $row['letter'] }}</td>
                <td class="td-en">{{ $row['total'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #888;">لا توجد مواد منجزة بعد</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="double-rule"></div>

@if (count($failedCourses) > 0)
    <div class="section-label">المواد المتبقية:</div>
    <table class="courses-table">
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
            @foreach ($failedCourses as $i => $row)
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
    <div class="double-rule"></div>
@endif

<p class="thesis-note">بحث رسالة الماجستير ( <span style="font-family:markazi;font-size:14pt;font-weight:normal;">6</span> ) وحدات</p>

<div class="double-rule"></div>

<div class="signature">
    <div dir="rtl">{{ $directorName }}</div>
    <div dir="rtl">مدير {{ $officeName }}</div>
</div>

</body>
</html>
