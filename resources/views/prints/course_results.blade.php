@php
    /** @var \App\Models\CourseOffering $offering */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');
    $deanName     = \App\Models\Setting::get('dean_name');

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

    .students-table { width: 100%; border-collapse: collapse; margin-bottom: 16pt; font-size: 12pt; }
    .students-table th,
    .students-table td { border: 1pt solid #000; padding: 4pt 5pt; text-align: center; }
    .students-table th { font-weight: bold; background: #f5f5f5; }
    .col-name { text-align: right; direction: rtl; }
    .col-enroll {
        font-family: 'markazi'; font-weight: normal; font-size: 14pt;
        direction: ltr; text-align: center;
    }
    .col-num { font-family: 'markazi'; font-weight: normal; font-size: 13pt; width: 10mm; }
    .col-grade {
        font-family: 'markazi'; font-weight: normal; font-size: 14pt;
        width: 20mm;
    }

    .sigs-area { margin-top: 16pt; }
    .instructor-sig { text-align: right; font-size: 12pt; margin-bottom: 20pt; }
    .sigs-table { width: 100%; border-collapse: collapse; }
    .sig-cell {
        width: 50%;
        text-align: right;
        direction: rtl;
        font-size: 12pt;
        line-height: 1.7;
        vertical-align: top;
        padding-top: 4pt;
    }
    .sig-title { font-weight: bold; }
    .sig-name { white-space: nowrap; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-office">{{ $officeName }}</div>
    <div class="header-title">نتيجة مقرر دراسي فصل {{ $semesterName }}</div>
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
            <th class="col-num">د.م</th>
            <th style="width:38mm;">رقم القيد</th>
            <th>اسم الطالـــب</th>
            <th style="width:22mm;">الفصل</th>
            <th style="width:22mm;">نهائي</th>
            <th style="width:22mm;">المجموع</th>
            <th style="width:22mm;">المعدل</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($enrollments as $i => $enrollment)
            @php $grade = $enrollment->grade; @endphp
            <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                <td class="col-num">
                    {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e((string) ($i + 1))) !!}
                </td>
                <td class="col-enroll">{{ $enrollment->student?->enrollment_no ?? '—' }}</td>
                <td class="col-name">{{ $enrollment->student?->user?->name ?? '—' }}</td>
                <td class="col-grade">{{ $grade?->coursework_grade ?? '—' }}</td>
                <td class="col-grade">{{ $grade?->final_grade ?? '—' }}</td>
                <td class="col-grade">{{ $grade?->total ?? '—' }}</td>
                <td class="col-grade">{{ $grade?->letter ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="sigs-area">
    <p class="instructor-sig">توقيع أستاذ المادة: .............................</p>

    <table class="sigs-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">مدير {{ $officeName }}</div>
                <div class="sig-name">{{ $directorName }}</div>
            </td>
            <td class="sig-cell" style="text-align: left; direction: ltr; padding-right: 16pt;">
                <div style="direction: rtl; text-align: right;">
                    <div class="sig-title">إعتماد السيد / عميد الكلية</div>
                    <div class="sig-name">{{ $deanName }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
