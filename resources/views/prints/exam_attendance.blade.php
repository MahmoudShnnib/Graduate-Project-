@php
    /** @var \App\Models\CourseOffering $offering */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');

    $specs       = $offering->course?->specializations ?? collect();
    $specialized = $specs->filter(fn ($s) => ($s->pivot->type ?? null) === 'specialized');
    $generals    = $specs->filter(fn ($s) => ($s->pivot->type ?? null) === 'general');
    $electives   = $specs->filter(fn ($s) => ($s->pivot->type ?? null) === 'elective');

    if ($specialized->isNotEmpty()) {
        $departmentLabel = $specialized->pluck('name')->implode('، ');
    } elseif ($generals->count() === $specs->count() && $generals->isNotEmpty()) {
        $departmentLabel = 'عام';
    } elseif ($electives->isNotEmpty() && $specialized->isEmpty() && $generals->isEmpty()) {
        $departmentLabel = 'اختياري';
    } else {
        $departmentLabel = $specs->pluck('name')->implode('، ') ?: '—';
    }

    $courseName   = $offering->course?->name ?? '—';
    $semesterName = $offering->semester?->name ?? '—';
    $facultyName  = $offering->faculty?->name ?? '—';
    $timeSlot     = $offering->time_slot ?: '—';

    $enrollments  = $offering->activeEnrollments->sortBy('student.enrollment_no')->values();
    $studentCount = $enrollments->count();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 13pt;
        line-height: 1.15;
        color: #000;
        direction: rtl;
        text-align: right;
    }
    .header { text-align: center; margin-bottom: 6pt; }
    .header-college { font-size: 16pt; font-weight: bold; margin-bottom: 2pt; }
    .header-office  { font-size: 14pt; font-weight: bold; margin-bottom: 4pt; }
    .header-title   { font-size: 13pt; font-weight: bold; margin-bottom: 8pt; }

    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8pt; font-size: 12pt; }
    .info-table td { border: 1pt solid #000; padding: 3pt 6pt; }
    .info-label { font-weight: bold; text-align: right; white-space: nowrap; width: 28mm; }
    .info-value { text-align: right; }

    .students-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 12pt; }
    .students-table th,
    .students-table td { border: 1pt solid #000; padding: 4pt 5pt; text-align: center; }
    .students-table th { font-weight: bold; background: #f5f5f5; }
    .col-name { text-align: right; direction: rtl; }
    .col-enroll {
        font-family: 'markazi'; font-weight: normal; font-size: 14pt;
        direction: ltr; text-align: center;
    }
    .col-num { font-family: 'markazi'; font-weight: normal; font-size: 13pt; width: 10mm; }
    .col-action { width: 22mm; }
    .col-notes  { width: 32mm; }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16pt;
        page-break-inside: avoid;
        font-size: 12pt;
        line-height: 1.6;
    }
    .footer-table td { padding: 0; vertical-align: top; }
    .footer-director { width: 55mm; direction: rtl; text-align: left; white-space: nowrap; }
    .footer-faculty  { direction: rtl; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-office">{{ $officeName }}</div>
    <div class="header-title">كشف حضور الإمتحان النهائي لمقرر دراسي بفصل {{ $semesterName }}</div>
</div>

<table class="info-table">
    <tr>
        <td class="info-label">اسم المقرر</td>
        <td class="info-value">{{ $courseName }}</td>
    </tr>
    <tr>
        <td class="info-label">القسم</td>
        <td class="info-value">{{ $departmentLabel }}</td>
    </tr>
    <tr>
        <td class="info-label">الأستاذ</td>
        <td class="info-value">{{ $facultyName }}</td>
    </tr>
    <tr>
        <td class="info-label">عدد الطلبة</td>
        <td class="info-value">
            {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:14pt;font-weight:normal;">$1</span>', e((string) $studentCount)) !!}
        </td>
    </tr>
    <tr>
        <td class="info-label">التاريخ</td>
        <td class="info-value">&nbsp;</td>
    </tr>
</table>

<table class="students-table">
    <thead>
        <tr>
            <th class="col-num">م</th>
            <th style="width:38mm;">رقم القيد</th>
            <th>اسم الطالـــب</th>
            <th class="col-action">دخول</th>
            <th class="col-action">خروج</th>
            <th class="col-notes">ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($enrollments as $i => $enrollment)
            <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                <td class="col-num">
                    {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e((string) ($i + 1))) !!}
                </td>
                <td class="col-enroll">{{ $enrollment->student?->enrollment_no ?? '—' }}</td>
                <td class="col-name">{{ $enrollment->student?->user?->name ?? '—' }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endforeach
        @for ($j = 0; $j < 3; $j++)
            <tr>
                <td class="col-num">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
    </tbody>
</table>

{{-- dir="ltr" forces physical left→right column order; RTL text is handled per-cell --}}
<table class="footer-table" dir="ltr">
    <tr>
        <td class="footer-director">
            <div>{{ $directorName }}</div>
            <div>مدير {{ $officeName }}</div>
        </td>
        <td class="footer-faculty">أستاذ المادة .............................</td>
    </tr>
</table>

</body>
</html>
