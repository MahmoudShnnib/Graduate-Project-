<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نموذج التوقف</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .field { margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>نموذج التوقف عن الدراسة</h1>
        <p>مكتب الدراسات العليا</p>
    </div>

    <div class="field"><span class="label">اسم الطالب:</span> {{ $request->student?->user?->name }}</div>
    <div class="field"><span class="label">رقم القيد:</span> {{ $request->student?->enrollment_no }}</div>
    <div class="field"><span class="label">الفصل الدراسي:</span> {{ $request->semester?->name }}</div>
    <div class="field"><span class="label">سبب التوقف:</span> {{ $request->reason }}</div>
    <div class="field"><span class="label">حالة الطلب:</span> {{ $request->status }}</div>
    <div class="field"><span class="label">تاريخ الطلب:</span> {{ $request->created_at?->format('Y-m-d') }}</div>
</body>
</html>
