<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نموذج الاستماحة</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 18px; }
        .field { margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .label { font-weight: bold; }
        .section { margin-top: 20px; border: 1px solid #333; padding: 10px; }
        .section h3 { margin: 0 0 10px 0; color: #1F4E79; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature { text-align: center; border-top: 1px solid #333; padding-top: 5px; width: 200px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>نموذج الاستماحة</h1>
        <p>مكتب الدراسات العليا</p>
    </div>

    <div class="field"><span class="label">اسم الطالب:</span> {{ $student->user?->name }}</div>
    <div class="field"><span class="label">رقم القيد:</span> {{ $student->enrollment_no }}</div>
    <div class="field"><span class="label">التخصص:</span> {{ $student->specialization?->name }}</div>
    <div class="field"><span class="label">المعدل التراكمي:</span> {{ number_format($student->gpa, 2) }}</div>

    <div class="section">
        <h3>الجهات المطلوب الاستماحة منها</h3>
        <p>□ المكتبة</p>
        <p>□ القسم الأكاديمي</p>
        <p>□ شؤون الطلاب</p>
        <p>□ الشؤون المالية</p>
        <p>□ تقنية المعلومات</p>
    </div>

    <div class="signature-row">
        <div class="signature">رئيس القسم</div>
        <div class="signature">مدير مكتب الدراسات العليا</div>
    </div>

    <p style="margin-top: 20px; font-size: 10px; color: #666;">
        تاريخ الإصدار: {{ now()->format('Y-m-d') }}
        @if($student->clearanceRequest)
            | حالة الاستماحة: {{ $student->clearanceRequest->status === 'completed' ? 'مكتملة' : 'قيد الإجراء' }}
        @endif
    </p>
</body>
</html>
