@php
    /** @var \App\Models\CourseOffering $courseOffering */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');

    $specs       = $courseOffering->course?->specializations ?? collect();
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

    $courseName   = $courseOffering->course?->name ?? '—';
    $courseCode   = $courseOffering->course?->code ?? '';
    $semesterName = $courseOffering->semester?->name ?? '—';
    $facultyName  = $courseOffering->faculty?->name ?? '—';
    $studentCount = $students->count();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 13pt;
        line-height: 1.4;
        color: #000;
        direction: rtl;
        text-align: right;
    }
    p { margin: 0; }

    .header { text-align: center; margin-bottom: 6pt; }
    .header-college { font-size: 17pt; font-weight: bold; margin-bottom: 2pt; }
    .header-office  { font-size: 15pt; font-weight: bold; }

    .doc-title {
        text-align: center;
        font-size: 15pt;
        font-weight: bold;
        margin: 10pt 0 12pt;
    }

    /* Info table */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        font-size: 13pt;
    }
    .info-table th,
    .info-table td {
        border: 1pt solid #000;
        padding: 4pt 8pt;
        text-align: right;
        direction: rtl;
    }
    .info-table th {
        font-weight: bold;
        width: 30%;
        white-space: nowrap;
    }
    .info-table .num-val {
        font-family: 'markazi';
        font-size: 15pt;
        font-weight: normal;
    }

    /* Section heading */
    .section-heading {
        font-size: 14pt;
        font-weight: bold;
        text-align: center;
        margin: 10pt 0 6pt;
    }

    /* Students table */
    .students-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12pt;
    }
    .students-table th,
    .students-table td {
        border: 1pt solid #000;
        padding: 4pt 6pt;
        text-align: right;
        direction: rtl;
    }
    .students-table th {
        font-weight: bold;
        font-size: 12pt;
    }
    .col-num {
        text-align: center;
        width: 28pt;
        white-space: nowrap;
    }
    .col-enroll {
        font-family: 'markazi';
        font-size: 14pt;
        font-weight: normal;
        direction: ltr;
        text-align: center;
        width: 60pt;
        white-space: nowrap;
    }
    .col-name { text-align: right; }
    .col-spec { text-align: right; width: 25%; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-office">{{ $officeName }}</div>
</div>

<div class="doc-title">
    نموذج مقرر دراسي لفصل
    {!! preg_replace('/(\d[\d\/]*)/', '<span style="font-family:markazi;font-size:16pt;font-weight:normal;">$1</span>', e($semesterName)) !!}
    م
</div>

<table class="info-table">
    <tr>
        <th>اسم المقرر</th>
        <td>
            {{ $courseName }}
            @if ($courseCode)
                <span style="font-family:dejavusans;font-size:11pt;direction:ltr;"> {{ $courseCode }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <th>القسم</th>
        <td>{{ $departmentLabel }}</td>
    </tr>
    <tr>
        <th>الاستاذ</th>
        <td>{{ $facultyName }}</td>
    </tr>
    <tr>
        <th>عدد الطلبة</th>
        <td class="num-val">{{ $studentCount }}</td>
    </tr>
</table>

<div class="section-heading">الطلاب المسجلين بالمقرر</div>

<table class="students-table">
    <thead>
        <tr>
            <th class="col-num">ر.م</th>
            <th class="col-enroll" style="text-align:center;">رقم القيد</th>
            <th class="col-name">اسم الطالب</th>
            <th class="col-spec">التخصص</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $index => $enrollment)
            <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                <td class="col-num">
                    <span style="font-family:markazi;font-size:14pt;font-weight:normal;">{{ $index + 1 }}</span>
                </td>
                <td class="col-enroll">{{ $enrollment->student->enrollment_no ?? '—' }}</td>
                <td class="col-name">{{ $enrollment->student->user->name ?? '—' }}</td>
                <td class="col-spec">{{ $enrollment->student->specialization?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#888;">لا يوجد طلاب مسجلون</td>
            </tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
