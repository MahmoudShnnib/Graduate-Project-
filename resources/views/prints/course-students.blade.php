<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كشف بأسماء الطلاب — {{ $courseOffering->course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 20mm;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #333;
            padding-bottom: 16px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .header h2 {
            font-size: 15px;
            font-weight: bold;
            color: #444;
        }
        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .info-item {
            display: inline-block;
            margin-left: 24px;
        }
        .info-item strong {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #555;
            padding: 8px 12px;
            text-align: right;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
        }
        td {
            font-size: 12px;
        }
        .row-num {
            text-align: center;
            width: 40px;
        }
        .signature-col {
            width: 120px;
        }
        .footer {
            margin-top: 48px;
            page-break-inside: avoid;
        }
        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-line {
            text-align: center;
            width: 200px;
        }
        .signature-line .line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .print-date {
            font-size: 11px;
            color: #666;
            margin-top: 12px;
        }
        @media print {
            body { padding: 10mm; }
            .no-print { display: none !important; }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
            z-index: 100;
        }
        .print-btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">طباعة</button>

    <div class="header">
        <h1>كشف بأسماء الطلاب المسجلين</h1>
        <h2>{{ $courseOffering->course->name }} ({{ $courseOffering->course->code }})</h2>
    </div>

    <div class="info">
        <span class="info-item"><strong>الفصل الدراسي:</strong> {{ $courseOffering->semester->name }}</span>
        <span class="info-item"><strong>عدد الطلاب:</strong> {{ $students->count() }}</span>
        <span class="info-item"><strong>أستاذ المادة:</strong> {{ $courseOffering->faculty?->name ?? '—' }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="row-num">م</th>
                <th>رقم القيد</th>
                <th>اسم الطالب</th>
                <th>التخصص</th>
                <th class="signature-col">التوقيع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $enrollment)
                <tr>
                    <td class="row-num">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; direction: ltr; text-align: center;">{{ $enrollment->student->enrollment_no }}</td>
                    <td>{{ $enrollment->student->user->name }}</td>
                    <td>{{ $enrollment->student->specialization?->name ?? '—' }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #999;">لا يوجد طلاب مسجلون</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-block">
            <div class="signature-line">
                <div class="line">رئيس القسم</div>
            </div>
            <div class="signature-line">
                <div class="line">أستاذ المادة</div>
            </div>
        </div>
        <p class="print-date">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</p>
    </div>
</body>
</html>
