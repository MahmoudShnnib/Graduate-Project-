<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف الدرجات</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: right; }
        th { background: #1F4E79; color: white; }
        .info-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .info-item { flex: 1; }
        .label { font-weight: bold; }
        .gpa { font-size: 14px; font-weight: bold; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>كشف الدرجات الأكاديمي</h1>
        <p>منظومة إدارة مكتب الدراسات العليا</p>
    </div>

    <div class="info-row">
        <div class="info-item"><span class="label">اسم الطالب:</span> {{ $student->user?->name }}</div>
        <div class="info-item"><span class="label">رقم القيد:</span> {{ $student->enrollment_no }}</div>
    </div>
    <div class="info-row">
        <div class="info-item"><span class="label">التخصص:</span> {{ $student->specialization?->name }}</div>
        <div class="info-item"><span class="label">تاريخ الطباعة:</span> {{ now()->format('Y-m-d') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>المقرر</th>
                <th>الرمز</th>
                <th>الساعات</th>
                <th>الفصل</th>
                <th>أعمال السنة</th>
                <th>النهائي</th>
                <th>المجموع</th>
                <th>التقدير</th>
                <th>النقاط</th>
            </tr>
        </thead>
        <tbody>
            @foreach($student->enrollments as $enrollment)
                @if($enrollment->grade)
                    <tr>
                        <td>{{ $enrollment->courseOffering?->course?->name }}</td>
                        <td>{{ $enrollment->courseOffering?->course?->code }}</td>
                        <td>{{ $enrollment->courseOffering?->course?->credits }}</td>
                        <td>{{ $enrollment->courseOffering?->semester?->name }}</td>
                        <td>{{ $enrollment->grade->coursework_grade ?? '-' }}</td>
                        <td>{{ $enrollment->grade->final_grade ?? '-' }}</td>
                        <td>{{ $enrollment->grade->total ?? '-' }}</td>
                        <td>{{ $enrollment->grade->letter ?? '-' }}</td>
                        <td>{{ $enrollment->grade->points ?? '-' }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="gpa">المعدل التراكمي: {{ number_format($student->gpa, 2) }}</div>
</body>
</html>
