<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $enrollments = $this->getEnrollments();
        $student = auth()->user()->student;
    @endphp

    @if(! $semester)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-calendar style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد فصل دراسي نشط حالياً.</p>
            </div>
        </x-filament::section>
    @elseif($enrollments->isEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-book-open style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>مقرراتي</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                        {{ $semester->name }}
                    </span>
                </div>
            </x-slot>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-400 dark:text-gray-500 text-sm">لم تُسجَّل في أي مقررات هذا الفصل بعد.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-book-open style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                            <span>مقرراتي</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                            {{ $semester->name }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                            {{ $enrollments->count() }} مقررات
                        </span>
                    </div>
                    <x-admin.print-button onclick="printEnrolledCourses()" />
                </div>
            </x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6"
                 id="courses-print-source"
                 data-student-name="{{ $student?->user?->name ?? '' }}"
                 data-enrollment-no="{{ $student?->enrollment_no ?? '' }}"
                 data-specialization="{{ $student?->specialization?->name ?? '' }}"
                 data-semester="{{ $semester->name }}"
            >
                <table class="fi-ta-table w-full table-auto text-sm text-right divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">المقرر</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">الرمز</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الوحدات</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">عضو هيئة التدريس</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الدرجة</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($enrollments as $enrollment)
                            <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="fi-ta-cell px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->courseOffering->course->code }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->faculty?->name ?? '—' }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center">
                                    @if($enrollment->grade?->published_at)
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $enrollment->grade->letter }}</span>
                                        <span class="text-gray-400 dark:text-gray-500 text-xs ms-1">({{ number_format((float)$enrollment->grade->total, 1) }})</span>
                                    @elseif($enrollment->grade?->midterm_published_at)
                                        <span class="text-xs font-medium text-info-600 dark:text-info-400">أعمال: {{ $enrollment->grade->coursework_grade }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center">
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $enrollment->status?->value === 'active',
                                        'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $enrollment->status?->value === 'dropped',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => ! in_array($enrollment->status?->value, ['active', 'dropped']),
                                    ])>{{ $enrollment->status?->label() ?? $enrollment->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @script
    <script>
        window.printEnrolledCourses = function() {
            const el = document.getElementById('courses-print-source');
            if (!el) return;

            const name   = el.getAttribute('data-student-name');
            const enrNo  = el.getAttribute('data-enrollment-no');
            const spec   = el.getAttribute('data-specialization');
            const sem    = el.getAttribute('data-semester');
            const now    = new Date();
            const date   = now.toLocaleDateString('en-CA');

            const tableRows = el.querySelectorAll('tbody tr');
            let rows = '';
            let totalCredits = 0;
            tableRows.forEach(function(tr, i) {
                const cells = tr.querySelectorAll('td');
                const courseName = cells[0]?.textContent?.trim() || '';
                const code       = cells[1]?.textContent?.trim() || '';
                const credits    = cells[2]?.textContent?.trim() || '';
                const faculty    = cells[3]?.textContent?.trim() || '';
                totalCredits += parseInt(credits) || 0;
                const bg = i % 2 === 1 ? ' style="background:#fafafa"' : '';
                rows += '<tr' + bg + '>'
                    + '<td style="width:40px;text-align:center">' + (i + 1) + '</td>'
                    + '<td>' + courseName + '</td>'
                    + '<td style="text-align:center;font-family:monospace;font-size:12px">' + code + '</td>'
                    + '<td style="text-align:center">' + credits + '</td>'
                    + '<td>' + faculty + '</td>'
                    + '</tr>';
            });

            const html = '<!DOCTYPE html>'
                + '<html dir="rtl" lang="ar"><head><meta charset="UTF-8">'
                + '<title>المقررات المسجلة — ' + name + '</title>'
                + '<style>'
                + '*{margin:0;padding:0;box-sizing:border-box}'
                + 'body{font-family:Arial,Tahoma,sans-serif;padding:40px;direction:rtl;color:#111}'
                + '.hdr{text-align:center;border-bottom:2px solid #111;padding-bottom:16px;margin-bottom:24px}'
                + '.hdr h1{font-size:15px;color:#444;margin-bottom:2px}'
                + '.hdr h2{font-size:20px;margin-bottom:12px}'
                + '.info{display:grid;grid-template-columns:1fr 1fr;gap:8px 32px;font-size:13px;margin-bottom:20px;text-align:right}'
                + '.info .label{color:#666}'
                + '.info .value{font-weight:bold}'
                + 'table{width:100%;border-collapse:collapse;font-size:14px}'
                + 'th,td{border:1px solid #ccc;padding:10px 14px;text-align:right}'
                + 'th{background:#f3f4f6;font-size:12px}'
                + '.total{text-align:left;font-size:13px;margin-top:8px;color:#333}'
                + '.ft{margin-top:40px;display:flex;justify-content:space-between;font-size:11px;color:#999;border-top:1px solid #ddd;padding-top:12px}'
                + '</style></head><body>'
                + '<div class="hdr">'
                + '<h1>كلية التقنية الإلكترونية — طرابلس</h1>'
                + '<h2>كشف المقررات المسجلة</h2>'
                + '</div>'
                + '<div class="info">'
                + '<div><span class="label">اسم الطالب: </span><span class="value">' + name + '</span></div>'
                + '<div><span class="label">رقم القيد: </span><span class="value">' + enrNo + '</span></div>'
                + '<div><span class="label">التخصص: </span><span class="value">' + spec + '</span></div>'
                + '<div><span class="label">الفصل الدراسي: </span><span class="value">' + sem + '</span></div>'
                + '</div>'
                + '<table><thead><tr>'
                + '<th style="width:40px;text-align:center">م</th>'
                + '<th>المقرر</th>'
                + '<th style="text-align:center">الرمز</th>'
                + '<th style="text-align:center">الوحدات</th>'
                + '<th>عضو هيئة التدريس</th>'
                + '</tr></thead>'
                + '<tbody>' + rows + '</tbody></table>'
                + '<p class="total">إجمالي الوحدات: <strong>' + totalCredits + '</strong></p>'
                + '<div class="ft"><span>مكتب الدراسات العليا</span><span>التاريخ: ' + date + '</span></div>'
                + '</body></html>';

            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
            w.onload = function() { w.print(); };
        };
    </script>
    @endscript
</x-filament-panels::page>
