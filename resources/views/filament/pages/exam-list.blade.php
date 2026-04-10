<x-filament-panels::page>
    @php
        $specializations = $this->getSpecializations();
        $applications    = $this->getApplications();
        $activeSpec      = $this->activeSpecialization;
        $activeSpecName  = $activeSpec ? $specializations->firstWhere('id', $activeSpec)?->name : null;
        $selectedCount   = count($this->selected);

        $tabs = collect([['id' => null, 'label' => 'الكل', 'count' => \App\Models\Application::where('status', 'initially_approved')->count()]]);
        foreach ($specializations as $spec) {
            $tabs->push([
                'id'    => $spec->id,
                'label' => $spec->name,
                'count' => \App\Models\Application::where('status', 'initially_approved')->where('specialization_id', $spec->id)->count(),
            ]);
        }
    @endphp

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <x-admin.filter-tabs :tabs="$tabs" :active="$activeSpec" wire-method="filterBySpecialization" />

        @if($activeSpec && $applications->isNotEmpty())
            <x-admin.print-button onclick="printExamList()" />
        @endif
    </div>

    {{-- Content --}}
    @if($applications->isEmpty())
        <x-admin.empty-state
            icon="heroicon-o-clipboard-document-list"
            :message="$activeSpec ? 'لا يوجد طلبات لقسم ' . $activeSpecName : 'لا يوجد طلبات مقبولة مبدئياً'"
        />
    @else
        <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" id="exam-list-table" data-spec="{{ $activeSpecName ?? '' }}" data-exam="{{ $applications->first()?->exam_date?->format('Y/m/d H:i') ?? '' }}">
            <div class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell w-10 print-hidden">
                                <input type="checkbox" x-data x-on:change="document.querySelectorAll('.exam-cb').forEach(c=>{c.checked=$event.target.checked;c.dispatchEvent(new Event('change'))})"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                            </th>
                            <th class="fi-ta-header-cell w-10">م</th>
                            <th class="fi-ta-header-cell">الاسم الرباعي</th>
                            @if(! $activeSpec)
                                <th class="fi-ta-header-cell">التخصص</th>
                            @endif
                            <th class="fi-ta-header-cell">الرقم الوطني</th>
                            <th class="fi-ta-header-cell">موعد الامتحان</th>
                            <th class="fi-ta-header-cell print-hidden">الدرجة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($applications as $i => $app)
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4 print-hidden">
                                    <input type="checkbox" value="{{ $app->id }}" wire:model="selected"
                                        class="exam-cb rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                                <td class="fi-ta-cell px-3 py-4 font-medium text-gray-950 dark:text-white" data-print-name>{{ $app->name }}</td>
                                @if(! $activeSpec)
                                    <td class="fi-ta-cell px-3 py-4">
                                        <x-admin.badge color="gray">{{ $app->specialization?->name ?? '—' }}</x-admin.badge>
                                    </td>
                                @endif
                                <td class="fi-ta-cell px-3 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $app->national_id ?? '—' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-gray-500 dark:text-gray-400">{{ $app->exam_date?->format('Y/m/d H:i') ?? '—' }}</td>
                                <td class="fi-ta-cell px-3 py-4 print-hidden">
                                    <input type="number" step="0.01" min="0" max="100"
                                        wire:model="scores.{{ $app->id }}" placeholder="—"
                                        class="w-20 rounded-lg border-gray-200 dark:border-gray-700 bg-transparent dark:text-white text-sm text-center focus:border-primary-500 focus:ring-primary-500" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Action bar inside the card --}}
            <div class="fi-ta-footer border-t border-gray-200 dark:border-white/10 px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if($selectedCount > 0)
                        تم تحديد <span class="font-bold text-gray-950 dark:text-white">{{ $selectedCount }}</span> طالب
                    @else
                        حدد الطلاب لتنفيذ إجراء
                    @endif
                </p>
                <div class="flex gap-2">
                    <x-filament::button wire:click="markFailed" wire:confirm="هل أنت متأكد من رفض الطلاب المحددين؟"
                        color="danger" icon="heroicon-o-x-mark" size="sm">
                        راسب — رفض
                    </x-filament::button>
                    <x-filament::button wire:click="markPassed" wire:confirm="هل أنت متأكد من قبول الطلاب المحددين نهائياً؟"
                        color="success" icon="heroicon-o-check" size="sm">
                        ناجح — قبول نهائي
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- Print --}}
    @script
    <script>
        window.printExamList = function() {
            const tableEl = document.getElementById('exam-list-table');
            const nameEls = tableEl.querySelectorAll('[data-print-name]');
            const spec  = tableEl.getAttribute('data-spec') || '';
            const exam  = tableEl.getAttribute('data-exam') || '';
            const now   = new Date();
            const day   = now.toLocaleDateString('ar-LY', { weekday: 'long' });
            const date  = now.toLocaleDateString('en-CA') + ' (' + day + ')';
            const count = nameEls.length;

            let rows = '';
            nameEls.forEach(function(el, i) {
                const bg = i % 2 === 1 ? ' style="background:#fafafa"' : '';
                rows += '<tr' + bg + '><td style="width:40px;text-align:center">' + (i + 1) + '</td><td>' + el.textContent.trim() + '</td></tr>';
            });

            const html = '<!DOCTYPE html>'
                + '<html dir="rtl" lang="ar"><head><meta charset="UTF-8">'
                + '<title>قائمة الامتحان التفاضلي</title>'
                + '<style>'
                + '*{margin:0;padding:0;box-sizing:border-box}'
                + 'body{font-family:Arial,Tahoma,sans-serif;padding:40px;direction:rtl;color:#111}'
                + '.hdr{text-align:center;border-bottom:2px solid #111;padding-bottom:16px;margin-bottom:24px}'
                + '.hdr h1{font-size:15px;color:#444;margin-bottom:2px}'
                + '.hdr h2{font-size:20px;margin-bottom:8px}'
                + '.meta{display:flex;justify-content:center;gap:32px;font-size:13px;color:#333}'
                + '.meta span{font-weight:bold}'
                + 'table{width:100%;border-collapse:collapse;font-size:14px;margin-top:4px}'
                + 'th,td{border:1px solid #ccc;padding:10px 14px;text-align:right}'
                + 'th{background:#f3f4f6;font-size:12px}'
                + '.ft{margin-top:40px;display:flex;justify-content:space-between;font-size:11px;color:#999;border-top:1px solid #ddd;padding-top:12px}'
                + '</style></head><body>'
                + '<div class="hdr">'
                + '<h1>كلية التقنية الإلكترونية — طرابلس</h1>'
                + '<h2>قائمة الامتحان التفاضلي</h2>'
                + '<div class="meta">'
                + '<div>التخصص: <span>' + spec + '</span></div>'
                + (exam ? '<div>موعد الامتحان: <span>' + exam + ' (' + new Date(exam.split(' ')[0]).toLocaleDateString('ar-LY', {weekday:'long'}) + ')</span></div>' : '')
                + '<div>عدد الطلاب: <span>' + count + '</span></div>'
                + '<div>التاريخ: <span>' + date + '</span></div>'
                + '</div>'
                + '</div>'
                + '<table><thead><tr><th style="width:40px;text-align:center">م</th><th>الاسم الرباعي</th></tr></thead>'
                + '<tbody>' + rows + '</tbody></table>'
                + '<div class="ft"><span>مكتب الدراسات العليا</span><span>التوقيع: ___________________</span></div>'
                + '</body></html>';

            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
            w.onload = function() { w.print(); };
        };
    </script>
    @endscript
</x-filament-panels::page>
