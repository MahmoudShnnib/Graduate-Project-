@php
    /** @var \App\Models\Student $student */
    /** @var \App\Models\Semester $semester */
    /** @var \Illuminate\Support\Collection $enrollments */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');

    $studentName    = $student->user?->name ?? '—';
    $enrollmentNo   = $student->enrollment_no ?? '—';
    $specialization = $student->specialization?->name ?? '—';
    $semesterName   = $semester->name ?? '—';
    $printDate      = now()->format('Y/m/d');

    $totalCredits = $enrollments->sum(fn ($e) => (int) ($e->courseOffering?->course?->credits ?? 0));
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

    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12pt; font-size: 13pt; }
    .info-table th,
    .info-table td { border: 1pt solid #000; padding: 4pt 8pt; text-align: right; direction: rtl; }
    .info-table th { font-weight: bold; width: 25%; white-space: nowrap; }
    .info-table .num-val { font-family: 'markazi'; font-size: 15pt; font-weight: normal; }

    .students-table { width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 8pt; }
    .students-table th,
    .students-table td { border: 1pt solid #000; padding: 4pt 6pt; text-align: right; direction: rtl; }
    .students-table th { font-weight: bold; font-size: 12pt; background: #f5f5f5; }
    .col-num { text-align: center; width: 28pt; white-space: nowrap; font-family: 'markazi'; font-size: 14pt; font-weight: normal; }
    .col-code {
        font-family: 'dejavusans'; font-size: 11pt;
        direction: ltr; text-align: center; width: 70pt; white-space: nowrap;
    }
    .col-credits { text-align: center; width: 50pt; font-family: 'markazi'; font-size: 14pt; font-weight: normal; }
    .col-name { text-align: right; }
    .col-faculty { text-align: right; width: 28%; }

    .total-line { font-size: 13pt; margin-bottom: 16pt; }
    .total-line .num-val { font-family: 'markazi'; font-size: 15pt; font-weight: bold; }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20pt;
        page-break-inside: avoid;
        font-size: 12pt;
        line-height: 1.6;
    }
    .footer-table td { padding: 0; vertical-align: top; }
    .footer-director { width: 60mm; direction: rtl; text-align: right; }
    .footer-date { direction: rtl; text-align: left; white-space: nowrap; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-office">{{ $officeName }}</div>
</div>

<div class="doc-title">
    كشف المقررات المسجلة
</div>

<table class="info-table">
    <tr>
        <th>اسم الطالب</th>
        <td>{{ $studentName }}</td>
    </tr>
    <tr>
        <th>رقم القيد</th>
        <td class="num-val">{{ $enrollmentNo }}</td>
    </tr>
    <tr>
        <th>التخصص</th>
        <td>{{ $specialization }}</td>
    </tr>
    <tr>
        <th>الفصل الدراسي</th>
        <td>{{ $semesterName }}</td>
    </tr>
</table>

<table class="students-table">
    <thead>
        <tr>
            <th class="col-num">م</th>
            <th class="col-name">المقرر</th>
            <th class="col-code">الرمز</th>
            <th class="col-credits">الوحدات</th>
            <th class="col-faculty">عضو هيئة التدريس</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($enrollments as $index => $enrollment)
            <tr style="{{ $loop->even ? 'background:#f0f0f0;' : '' }}">
                <td class="col-num">{{ $index + 1 }}</td>
                <td class="col-name">{{ $enrollment->courseOffering?->course?->name ?? '—' }}</td>
                <td class="col-code">{{ $enrollment->courseOffering?->course?->code ?? '—' }}</td>
                <td class="col-credits">{{ $enrollment->courseOffering?->course?->credits ?? '—' }}</td>
                <td class="col-faculty">{{ $enrollment->courseOffering?->faculty?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#888;">لا يوجد مقررات مسجلة</td>
            </tr>
        @endforelse
    </tbody>
</table>

<p class="total-line">
    إجمالي الوحدات:
    <span class="num-val">{{ $totalCredits }}</span>
</p>

{{-- dir="ltr" forces physical left→right column order; RTL text is handled per-cell --}}
<table class="footer-table" dir="ltr">
    <tr>
        <td class="footer-date">
            التاريخ:
            {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e($printDate)) !!}
        </td>
        <td class="footer-director">
            <div>{{ $directorName }}</div>
            <div>مدير {{ $officeName }} بالكلية</div>
        </td>
    </tr>
</table>

</body>
</html>
