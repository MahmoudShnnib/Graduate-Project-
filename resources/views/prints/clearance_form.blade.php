@php
    // Blank clearance template — every field is filled in by hand, no student data is rendered.
    $collegeName = \App\Models\Setting::get('college_name');
    $officeName  = \App\Models\Setting::get('office_name');

    $departments = [
        'المحاور العلمية',
        'المكتبة',
        'الأستاذ المشرف',
        'الدراسات العليا',
    ];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    /* Pull the page margin in so the bordered frame sits out in the margin area;
       with the frame padding (2.5mm + 9mm) the text still lands at ~25.4mm — the
       same start position as every other (borderless) print template. */
    @page { margin: 14mm; }

    body {
        font-family: 'noto-naskh', serif;
        font-size: 13pt;
        line-height: 1.3;
        color: #000;
        direction: rtl;
        text-align: right;
    }

    .frame {
        border: 1pt solid #000;
        padding: 2.5mm;
    }
    .frame-inner {
        border: 0.5pt solid #000;
        padding: 4mm 9mm;
    }

    /* Spacer to push the notes section toward the bottom of the page. */
    .notes-spacer { height: 22mm; }

    .title-1 { font-size: 19pt; font-weight: bold; text-align: center; margin: 0 0 5pt; }
    .title-2 { font-size: 25pt; font-weight: bold; text-align: center; margin: 0 0 8pt; }

    .date-line { text-align: left; font-size: 15pt; margin: 4pt 0 8pt; }

    /* Fill-in information block */
    .info { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .info td { padding: 6pt 0 3pt; vertical-align: bottom; font-size: 15pt; }
    .info td.lbl { white-space: nowrap; padding-left: 3pt; }
    .info td.lbl.cont { padding-right: 14pt; }
    .info td.fill { border-bottom: 1.4pt dotted #000; }

    .request-line { font-weight: bold; font-size: 16pt; text-align: center; margin: 6pt 0 8pt; }

    /* Approval table */
    .approval { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .approval th, .approval td {
        border: 1pt solid #000;
        text-align: center;
        direction: rtl;
    }
    .approval th { font-weight: bold; font-size: 15pt; padding: 6pt; background: #f0f0f0; }
    .approval td { height: 9mm; }
    .approval .dept { font-weight: bold; font-size: 14pt; padding: 4pt 8pt; text-align: center; }

    /* Approval / accreditation box */
    .accredit { width: 95mm; margin: 0 auto 12pt; border-collapse: collapse; }
    .accredit .head {
        border: 1pt solid #000;
        text-align: center;
        font-weight: bold;
        font-size: 14pt;
        padding: 6pt;
    }
    .accredit .body { border: 1pt solid #000; border-top: 0; height: 10mm; }

    /* Notes */
    .notes { font-size: 13pt; line-height: 1.5; text-align: right; }
    .notes .note-main { margin-bottom: 8pt; }
    .notes .note-lbl { font-weight: bold; text-decoration: underline; }
    .notes .bullet { margin: 3pt 0; }
    .notes .bullet b { font-weight: bold; }
</style>
</head>
<body>
<div class="frame">
<div class="frame-inner">

    <div class="title-1">{{ $officeName }} ب{{ $collegeName }}</div>
    <div class="title-2">إخلاء طرف طالب دراسات عليا</div>

    <div class="date-line">التاريخ : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>

    <table class="info">
        <tr>
            <td class="lbl" style="width:22%;">الاسم بالكامل :</td>
            <td class="fill" style="width:32%;">&nbsp;</td>
            <td class="lbl cont" style="width:16%;">رقم القيد :</td>
            <td class="fill" style="width:30%;">&nbsp;</td>
        </tr>
        <tr>
            <td class="lbl" style="width:18%;">الرقم الوطني :</td>
            <td class="fill" style="width:19%;">&nbsp;</td>
            <td class="lbl cont" style="width:13%;">الجنسية :</td>
            <td class="fill" style="width:18%;">&nbsp;</td>
            <td class="lbl cont" style="width:11%;">هاتف :</td>
            <td class="fill" style="width:21%;">&nbsp;</td>
        </tr>
        <tr>
            <td class="lbl" style="width:11%;">القسم :</td>
            <td class="fill" colspan="5">&nbsp;</td>
        </tr>
        <tr>
            <td class="lbl" style="width:22%;">عنوان السكن :</td>
            <td class="fill" colspan="5">&nbsp;</td>
        </tr>
        <tr>
            <td class="lbl" style="width:22%;">عنوان البحث :</td>
            <td class="fill" colspan="5">&nbsp;</td>
        </tr>
        <tr>
            <td class="lbl" style="width:25%;">تاريخ المناقشة :</td>
            <td class="fill" style="width:27%;">&nbsp;</td>
            <td class="lbl cont" style="width:22%;">تاريخ التسليم :</td>
            <td class="fill" style="width:26%;">&nbsp;</td>
        </tr>
    </table>

    <div class="request-line">يطلب اعتماد النموذج من الإدارات والأقسام الآتية :</div>

    <table class="approval">
        <thead>
            <tr>
                <th style="width:30%;">الأقسام</th>
                <th style="width:30%;">الاسم</th>
                <th style="width:22%;">التوقيع</th>
                <th style="width:18%;">الختم</th>
            </tr>
        </thead>
        <tbody>
            @foreach($departments as $dept)
                <tr>
                    <td class="dept">{{ $dept }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="accredit">
        <tr><td class="head">اعتماد {{ $officeName }} ب{{ $collegeName }}</td></tr>
        <tr><td class="body"></td></tr>
    </table>

    <div class="notes-spacer"></div>

    <div class="notes">
        <p class="note-main">
            <span class="note-lbl">ملاحظة :</span>
            لا يسلم كشف وإفادة التخرج وكذلك الشهادة الجامعية أو ملف الطالب الأبيض إلا بعد اعتماد هذا
            النموذج من الجهات المذكورة أعلاه ، كما يعد هذا النموذج من نسختين :
        </p>
        <p class="bullet">• <b>الأولى</b> : تسلم لمكتب شؤون الخريجين بالكلية .</p>
        <p class="bullet">• <b>الثانية</b> : تسلم لإدارة الدراسات العليا لإتمام الإجراءات .</p>
    </div>

</div>
</div>
</body>
</html>
