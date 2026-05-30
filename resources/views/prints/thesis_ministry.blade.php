@php
    /** @var \App\Models\ThesisRegistration $thesis */
    /** @var \App\Models\Student $student */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');
    $deanName     = \App\Models\Setting::get('dean_name');

    $logoPath = public_path('images/university-logo.png');
    $logoData = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
        : null;

    $studentName     = $student->user?->name ?? '—';
    $enrollmentNo    = $student->enrollment_no ?? '—';
    $nationalId      = $student->national_id ?? '—';
    $birthDate       = $student->birth_date?->format('d/m/Y') ?? '—';
    $birthPlace      = $student->birth_place ?? '—';
    $nationality     = $student->nationality ?? '—';
    $gender          = $student->gender ?? '—';
    $phone           = $student->user?->phone ?? '—';
    $email           = $student->user?->email ?? '—';
    $qualification   = $student->user?->qualification ?? 'بكالوريوس';
    $specialization  = $student->specialization?->name ?? '—';

    $registrationSemesterName = $registrationSemester?->name ?? '—';
    $lastSemesterName         = $lastSemester?->name ?? '—';
    $gpa                      = $student->gpa !== null ? number_format((float) $student->gpa, 2) : '—';

    // Supervisor data
    if ($thesis->is_external_supervisor && $thesis->supervisor) {
        $sup = $thesis->supervisor;
        $supName          = $sup->name;
        $supNationalId    = $sup->national_id ?? '—';
        $supBirthDate     = $sup->birth_date?->format('d/m/Y') ?? '—';
        $supBirthPlace    = $sup->birth_place ?? '—';
        $supNationality   = $sup->nationality ?? '—';
        $supGender        = $sup->gender ?? '—';
        $supAcademicRank  = $sup->academic_rank ?? '—';
        $supQualification = $sup->qualification ?? '—';
        $supPhone         = $sup->phone ?? '—';
        $supEmail         = $sup->email ?? '—';
        $supInstitution   = $sup->institution ?? '—';
    } elseif (! $thesis->is_external_supervisor && $thesis->facultySupervisor) {
        $fac = $thesis->facultySupervisor;
        $supName          = $fac->name;
        $supNationalId    = $fac->national_id ?? '—';
        $supBirthDate     = $fac->birth_date?->format('d/m/Y') ?? '—';
        $supBirthPlace    = $fac->birth_place ?? '—';
        $supNationality   = $fac->nationality ?? '—';
        $supGender        = $fac->gender ?? '—';
        $supAcademicRank  = $fac->academic_rank ?? '—';
        $supQualification = $fac->qualification ?? '—';
        $supPhone         = $fac->phone ?? '—';
        $supEmail         = $fac->email ?? '—';
        $supInstitution   = $fac->institution ?? '—';
    } else {
        $supName          = $thesis->supervisor_name ?? '—';
        $supNationalId    = '—';
        $supBirthDate     = '—';
        $supBirthPlace    = '—';
        $supNationality   = '—';
        $supGender        = '—';
        $supAcademicRank  = $thesis->supervisor_degree ?? '—';
        $supQualification = '—';
        $supPhone         = '—';
        $supEmail         = '—';
        $supInstitution   = $thesis->supervisor_employer ?? '—';
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 12pt;
        line-height: 1.3;
        color: #000;
        direction: rtl;
        text-align: right;
    }
    p { margin: 0 0 6pt 0; }

    .page-border {
        border: 2pt solid #000;
        padding: 10mm;
        min-height: 230mm;
    }

    /* Page header (3-column) */
    .page-header {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10pt;
    }
    .page-header td { vertical-align: top; padding: 0 4pt; }
    .header-en { font-size: 9pt; line-height: 1.5; direction: ltr; text-align: left; width: 42%; }
    .header-logo { text-align: center; width: 16%; vertical-align: middle; }
    .header-ar { font-size: 10pt; line-height: 1.5; text-align: right; width: 42%; }

    .main-title {
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        margin: 8pt 0 4pt;
    }
    .salutation { font-size: 12pt; margin-bottom: 4pt; }
    .intro-para { font-size: 11pt; margin-bottom: 10pt; text-align: justify; }

    /* Section headings */
    .section-heading {
        font-size: 12pt;
        font-weight: bold;
        margin: 8pt 0 4pt;
        text-decoration: underline;
    }

    /* Data tables */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8pt;
        font-size: 11pt;
    }
    .data-table th,
    .data-table td {
        border: 1pt solid #000;
        padding: 3pt 5pt;
        text-align: right;
        direction: rtl;
    }
    .data-table th { font-weight: bold; background: #f0f0f0; white-space: nowrap; width: 25%; }
    .data-table .enroll-val {
        font-family: 'markazi';
        font-weight: normal;
        font-size: 13pt;
        direction: ltr;
        text-align: right;
    }

    /* Footer signatures */
    .sigs-table { width: 100%; border-collapse: collapse; margin-top: 20pt; }
    .sig-cell {
        width: 50%;
        text-align: right;
        direction: rtl;
        font-size: 12pt;
        line-height: 1.8;
        vertical-align: top;
    }
    .sig-title { font-weight: bold; }
    .sig-name { white-space: nowrap; }
</style>
</head>
<body>

{{-- ===== PAGE 1 ===== --}}
<div class="page-border">

    {{-- Header --}}
    <table class="page-header">
        <tr>
            <td class="header-ar">
                وزارة التعليم التقني والفني<br>
                ادارة الكليات التقنية<br>
                {{ $collegeName }}
            </td>
            <td class="header-logo">@if ($logoData)<img src="{{ $logoData }}" style="width: 22mm;">@endif</td>
            <td class="header-en">
                Ministry of Technical<br>
                Vocational Education College<br>
                of Electronic Technology
            </td>
        </tr>
    </table>

    <div class="main-title">السيد المحترم / مدير ادارة الدراسات العليا بوزارة التعليم التقني والفني</div>

    <p class="salutation">تحسية طيبة وبعد،،،،</p>
    <p class="intro-para">
        بهذا نحيل اليكم ... بيانات طالب الماجستير المبين اسمه أدناه لغرض تسجيل عنوان أطروحته
        لنيل درجة الماجستير في مجال تخصصه وانهاء كافة الاجراءات اللازمة بالخصوص .
    </p>

    {{-- Section 1: Thesis Info --}}
    <div class="section-heading">أولاً : بيانات عن الرسالة</div>
    <table class="data-table">
        <tr>
            <th>الكلية</th>
            <td colspan="3">{{ $collegeName }}</td>
        </tr>
        <tr>
            <th rowspan="2">عنوان الرسالة</th>
            <th style="width:18%;">بالغة العربية</th>
            <td colspan="2">{{ $thesis->title ?? '—' }}</td>
        </tr>
        <tr>
            <th>بالغة الانجليزية</th>
            <td colspan="2" style="direction:ltr; text-align:left;">{{ $thesis->title_en ?? '—' }}</td>
        </tr>
        <tr>
            <th>درجة الاجازة العلمية للبحث</th>
            <td>الماجستير</td>
            <th style="width:18%;">القسم العلمي</th>
            <td>{{ $specialization }}</td>
        </tr>
    </table>

    {{-- Section 2: Student Info --}}
    <div class="section-heading">ثانياً : بيانات الطالب والشهادات العلمية</div>
    <table class="data-table">
        <tr>
            <th>اسم الطالب</th>
            <td>{{ $studentName }}</td>
            <th>رقم القيد</th>
            <td class="enroll-val">{{ $enrollmentNo }}</td>
        </tr>
        <tr>
            <th>تاريخ الميلاد</th>
            <td>
                {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e($birthDate)) !!}
            </td>
            <th>مكان الميلاد</th>
            <td>{{ $birthPlace }}</td>
        </tr>
        <tr>
            <th>الرقم الوطني</th>
            <td class="enroll-val">{{ $nationalId }}</td>
            <th>الجنسية</th>
            <td>{{ $nationality }}</td>
        </tr>
        <tr>
            <th>الشهادة العلمية</th>
            <td>{{ $qualification }}</td>
            <th>الجنس</th>
            <td>{{ $gender }}</td>
        </tr>
        <tr>
            <th>رقم الهاتف</th>
            <td class="enroll-val">{{ $phone }}</td>
            <th>البريد الالكتروني</th>
            <td style="direction:ltr; text-align:right;">{{ $email }}</td>
        </tr>
        <tr>
            <th>تاريخ بداية التسجيل في الدراسات العليا</th>
            <td colspan="3">{{ $registrationSemesterName }}</td>
        </tr>
        <tr>
            <th>تاريخ الانتهاء من المقررات الدراسية المطلوبة</th>
            <td>{{ $lastSemesterName }}</td>
            <th>المعدل التراكمي</th>
            <td>
                {!! preg_replace('/(\d[\d,.]*)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e($gpa)) !!}
            </td>
        </tr>
    </table>

    <div class="section-heading">ثالثاً : بيانات الأستاذ المشرف والأكاديمية</div>
    <p style="font-size: 10pt; color: #555;">(يُكمل في الصفحة التالية)</p>

</div>

{{-- ===== PAGE 2 ===== --}}
<div class="page-border" style="page-break-before: always; margin-top: 0;">

    {{-- Header repeat --}}
    <table class="page-header">
        <tr>
            <td class="header-ar">
                وزارة التعليم التقني والفني<br>
                ادارة الكليات التقنية<br>
                {{ $collegeName }}
            </td>
            <td class="header-logo">@if ($logoData)<img src="{{ $logoData }}" style="width: 22mm;">@endif</td>
            <td class="header-en">
                Ministry of Technical<br>
                Vocational Education College<br>
                of Electronic Technology
            </td>
        </tr>
    </table>

    <div class="section-heading">ثالثاً : بيانات الأستاذ المشرف والأكاديمية</div>
    <table class="data-table">
        <tr>
            <th>اسم المشرف</th>
            <td>{{ $supName }}</td>
            <th>الرقم الوطني</th>
            <td class="enroll-val">{{ $supNationalId }}</td>
        </tr>
        <tr>
            <th>تاريخ الميلاد</th>
            <td>
                {!! preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:13pt;font-weight:normal;">$1</span>', e($supBirthDate)) !!}
            </td>
            <th>مكان الميلاد</th>
            <td>{{ $supBirthPlace }}</td>
        </tr>
        <tr>
            <th>الجنسية</th>
            <td>{{ $supNationality }}</td>
            <th>الجنس</th>
            <td>{{ $supGender }}</td>
        </tr>
        <tr>
            <th>الدرجة الاكاديمية</th>
            <td>{{ $supAcademicRank }}</td>
            <th>المؤهل العلمي</th>
            <td>{{ $supQualification }}</td>
        </tr>
        <tr>
            <th>رقم الهاتف</th>
            <td class="enroll-val">{{ $supPhone }}</td>
            <th>البريد الالكتروني</th>
            <td style="direction:ltr; text-align:right;">{{ $supEmail }}</td>
        </tr>
        <tr>
            <th>جهة العمل</th>
            <td colspan="3">{{ $supInstitution }}</td>
        </tr>
    </table>

    <table class="sigs-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">اعداد {{ $officeName }}</div>
                <div class="sig-name">{{ $directorName }}</div>
            </td>
            <td class="sig-cell" style="text-align: left; direction: ltr;">
                <div style="direction: rtl; text-align: right;">
                    <div class="sig-title">اعتماد عميد الكلية</div>
                    <div class="sig-name">{{ $deanName }}</div>
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
