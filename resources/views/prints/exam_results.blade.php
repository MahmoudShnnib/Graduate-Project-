@php
    /** @var \Illuminate\Support\Collection $applications */
    /** @var string $specName */
    /** @var bool $showSpecColumn */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');

    $todayText    = now()->format('Y/m/d');
    $studentCount = $applications->count();
    $passedCount  = $applications->where('exam_passed', true)->count();
    $failedCount  = $studentCount - $passedCount;

    $num = fn ($v) => preg_replace(
        '/(\d[\d:\/.]*)/',
        '<span style="font-family:markazi;font-size:14pt;font-weight:normal;">$1</span>',
        e((string) $v)
    );
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 13pt;
        line-height: 1.2;
        color: #000;
        direction: rtl;
        text-align: right;
    }
    .header { text-align: center; margin-bottom: 6pt; border-bottom: 2pt solid #000; padding-bottom: 8pt; }
    .header-college { font-size: 16pt; font-weight: bold; margin-bottom: 2pt; }
    .header-title   { font-size: 18pt; font-weight: bold; margin-top: 4pt; }

    .meta-table { width: 100%; border-collapse: collapse; margin: 10pt 0; font-size: 12pt; }
    .meta-table td { padding: 2pt 4pt; }
    .meta-label { font-weight: bold; white-space: nowrap; }

    .students-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 13pt; }
    .students-table th,
    .students-table td { border: 1pt solid #000; padding: 6pt 8pt; text-align: right; }
    .students-table th { font-weight: bold; background: #f3f4f6; font-size: 12pt; }
    .col-num { font-family: 'markazi'; font-weight: normal; font-size: 14pt; width: 12mm; text-align: center; }
    .col-score { text-align: center; width: 22mm; }
    .col-result { text-align: center; width: 26mm; font-weight: bold; }
    .passed { color: #047857; }
    .failed { color: #b91c1c; }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30pt;
        page-break-inside: avoid;
        font-size: 11pt;
    }
    .footer-table td { padding: 0; vertical-align: top; line-height: 1.6; }
</style>
</head>
<body>

<div class="header">
    <div class="header-college">{{ $collegeName }}</div>
    <div class="header-title">نتائج الامتحان التفاضلي</div>
</div>

<table class="meta-table" dir="ltr">
    <tr>
        <td style="text-align:right;"><span class="meta-label">التخصص:</span> {{ $specName }}</td>
        <td style="text-align:left;"><span class="meta-label">عدد الطلاب:</span> {!! $num($studentCount) !!}</td>
    </tr>
    <tr>
        <td style="text-align:right;"><span class="meta-label">المقبولون:</span> {!! $num($passedCount) !!} &nbsp;—&nbsp; <span class="meta-label">المرفوضون:</span> {!! $num($failedCount) !!}</td>
        <td style="text-align:left;"><span class="meta-label">التاريخ:</span> {!! $num($todayText) !!}</td>
    </tr>
</table>

<table class="students-table">
    <thead>
        <tr>
            <th class="col-num">م</th>
            <th>الاسم الرباعي</th>
            @if($showSpecColumn)<th>التخصص</th>@endif
            <th class="col-score">الدرجة</th>
            <th class="col-result">النتيجة</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($applications as $i => $app)
            <tr style="{{ $loop->even ? 'background:#f7f7f7;' : '' }}">
                <td class="col-num">{!! $num($i + 1) !!}</td>
                <td>{{ $app->name }}</td>
                @if($showSpecColumn)<td>{{ $app->specialization?->name ?? '—' }}</td>@endif
                <td class="col-score">{!! $app->exam_score !== null ? $num($app->exam_score) : '—' !!}</td>
                <td class="col-result {{ $app->exam_passed ? 'passed' : 'failed' }}">{{ $app->exam_passed ? 'مقبول' : 'مرفوض' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $showSpecColumn ? 5 : 4 }}" style="text-align:center;">لا يوجد نتائج</td></tr>
        @endforelse
    </tbody>
</table>

<table class="footer-table" dir="ltr">
    <tr>
        <td style="text-align:left;">التوقيع: .............................</td>
        <td style="text-align:right;">
            <div>{{ $directorName }}</div>
            <div>مدير {{ $officeName }}</div>
        </td>
    </tr>
</table>

</body>
</html>
