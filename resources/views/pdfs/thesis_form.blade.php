<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نموذج تسجيل الرسالة</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .field { margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>نموذج تسجيل عنوان الرسالة</h1>
        <p>مكتب الدراسات العليا — للوزارة</p>
    </div>

    <div class="field"><span class="label">اسم الطالب:</span> {{ $registration->student?->user?->name }}</div>
    <div class="field"><span class="label">رقم القيد:</span> {{ $registration->student?->enrollment_no }}</div>
    <div class="field"><span class="label">التخصص:</span> {{ $registration->student?->specialization?->name }}</div>
    <div class="field"><span class="label">عنوان الرسالة:</span> {{ $registration->title }}</div>
    <div class="field"><span class="label">اسم المشرف:</span> {{ $registration->supervisor_name }}</div>
    <div class="field"><span class="label">درجة المشرف:</span> {{ $registration->supervisor_degree }}</div>
    <div class="field"><span class="label">جهة عمل المشرف:</span> {{ $registration->supervisor_employer }}</div>
    <div class="field"><span class="label">تاريخ التسجيل:</span> {{ $registration->created_at?->format('Y-m-d') }}</div>
</body>
</html>
