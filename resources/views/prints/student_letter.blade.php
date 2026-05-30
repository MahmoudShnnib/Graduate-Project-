@php
    /** @var \App\Models\Student $student */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');
    $studentName  = $student->user->name;
    $enrollmentNo = $student->enrollment_no;
    $specialization = $student->specialization?->name ?? '—';
    $regSemester  = $student->registration_semester?->name;
    $regSemesterHtml = $regSemester
        ? preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:22pt;font-weight:normal;">$1</span>', e($regSemester))
        : null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 16pt;
        line-height: 1.08;
        color: #000;
        direction: rtl;
        text-align: center;
    }
    p { margin: 0 0 8pt 0; text-align: center; }

    .title {
        font-size: 20pt;
        font-weight: bold;
        font-style: normal;
        text-align: center;
        margin: 44mm 0 16pt 0;
    }
    .greeting {
        font-size: 16pt;
        font-style: italic;
        text-align: right;
        margin: 0 0 8pt 0;
    }
    .body-line {
        font-size: 16pt;
        line-height: 1.5;
        text-align: center;
        margin: 0 0 8pt 0;
    }
    .student-name {
        font-size: 16pt;
        text-align: center;
        margin: 0 0 8pt 0;
    }
    .registration {
        font-size: 16pt;
        line-height: 1.5;
        text-align: center;
        margin: 0 0 8pt 0;
    }
    .bold { font-weight: bold; }
    .enrollment-no { font-family: 'markazi'; font-weight: normal; font-size: 22pt; }
    .closing {
        font-size: 16pt;
        line-height: 1.5;
        text-align: center;
        margin: 0 0 8pt 0;
    }
    .salam {
        font-size: 16pt;
        text-align: center;
        margin: 16pt 0 40pt 0;
    }

    /* Signature — flows below salam, floated to left side of page */
    .signature {
        margin-top: 40pt;          /* ~2 blank lines below salam (was 3, moved up by 1) */
        float: left;
        clear: both;
        width: 55mm;                /* very narrow — longer line overflows leftward */
        text-align: right;          /* RTL natural — required for Arabic shaping */
        direction: rtl;
        font-size: 12pt;
        line-height: 1.6;
        overflow: visible;
    }
    .signature > div {
        direction: rtl;
        text-align: right;
        white-space: nowrap;        /* let line extend beyond the block to the left */
    }

    /* CC / footer block — flows 1 line below signature, right side */
    .cc-block {
        clear: both;                /* clear the signature float */
        margin-top: 18pt;          /* ~1 blank line below signature */
        text-align: right;
        direction: rtl;
        font-size: 10pt !important;
        line-height: 1.5;
    }
    .cc-divider {
        font-size: 10pt !important;
        line-height: 1;
        margin: 0 0 4pt 0;
        letter-spacing: 0;
    }
    .cc-inner {
        display: block;
        margin-left: auto;
        margin-right: 0;
        border-collapse: collapse;
        font-size: 10pt !important;
        direction: rtl;
    }
    .cc-inner tr, .cc-inner td {
        font-size: 10pt !important;
        line-height: 1.5;
        vertical-align: top;
        padding: 0;
        text-align: right;
        color: #000;
    }
    .cc-label {
        white-space: nowrap;
        padding-left: 8pt;
        font-size: 10pt !important;
    }
    .cc-items {
        font-size: 10pt !important;
    }
</style>
</head>
<body>

<div style="height: 15mm; line-height: 1; font-size: 1pt;">&nbsp;</div>

<p class="title">إلى من يهــمه الأمــر</p>

<p class="greeting"><i>بعد التحية ،،،،</i></p>

<p class="body-line">يفيدكم {{ $officeName }} ب{{ $collegeName }} بأن</p>

<p class="student-name"><span style="font-weight:bold;font-style:italic;">الطالب</span> / <b>{{ $studentName }}</b></p>

<p class="registration">
    هو أحد طلبة الماجستير بالكلية و مسجل تحت رقم قيد
    <span class="enrollment-no">{{ $enrollmentNo }}</span>
    تخصص {{ $specialization }}
    @if($regSemesterHtml)
        <span class="bold">منذ الفصل الدراسي {!! $regSemesterHtml !!} و مستمر بدراسته الى حد الآن .</span>
    @else
        <span class="bold">و مستمر بدراسته الى حد الآن .</span>
    @endif
</p>

<p class="closing">أعطيت له هذا الافادة بناءً على طلبه لإستخدامه فيما لا يتعارض مع القانون .</p>

<p>&nbsp;</p>

<p class="salam">و الســـلام عليـكم و رحمـة الله و بركاتـه</p>

<div class="signature">
    <div dir="rtl">{{ $directorName }}</div>
    <div dir="rtl">مدير {{ $officeName }} بالكلية</div>
</div>

<div class="cc-block">
    <div class="cc-divider">--------------------</div>
    <table class="cc-inner">
        <tr>
            <td class="cc-label">صورة الى:</td>
            <td class="cc-items">- ملف الطالب<br>- صادر القسم</td>
        </tr>
    </table>
</div>

</body>
</html>
