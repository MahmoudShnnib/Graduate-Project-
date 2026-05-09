<x-filament-panels::page>
    @php
        $isDiscussionCompleted = $this->isDiscussionCompleted();
        $thesis = auth()->user()->student?->latestThesisRegistration;
        $discussion = auth()->user()->student?->latestDiscussionRequest;
        $student = auth()->user()->student;
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- Prerequisites Check --}}
    @if(! $isDiscussionCompleted)
        <x-filament::section>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                    <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <p class="font-semibold text-warning-800 dark:text-warning-300 text-sm">الاستماحة غير متاحة بعد</p>
                    <p class="text-sm text-warning-700 dark:text-warning-400 mt-0.5">يجب إتمام المناقشة والتجليد والتسليم قبل طباعة نموذج الاستماحة.</p>
                    @if($discussion)
                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="text-warning-700 dark:text-warning-400">حالة المناقشة:</span>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Pending, \App\Enums\DiscussionStatus::AcceptedWithEdits]),
                                'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Scheduled, \App\Enums\DiscussionStatus::Discussed]),
                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $discussion->status === \App\Enums\DiscussionStatus::DiscussionApproved,
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Rejected, \App\Enums\DiscussionStatus::DiscussionRejected]),
                            ])>{{ $discussion->status->studentLabel() }}</span>
                        </div>
                    @elseif($thesis && $thesis->status === \App\Enums\ThesisStatus::Approved)
                        <p class="text-sm text-warning-600 dark:text-warning-500 mt-1">رسالتك مقبولة — يرجى تقديم طلب المناقشة أولاً.</p>
                    @else
                        <p class="text-sm text-warning-600 dark:text-warning-500 mt-1">لم تقدم طلب تسجيل رسالة بعد أو لم يتم قبولها.</p>
                    @endif
                </div>
            </div>
        </x-filament::section>

    {{-- Discussion completed — show print button --}}
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-check-badge style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>نموذج الاستماحة</span>
                    </div>
                    <x-admin.print-button onclick="printClearanceForm()">
                        طباعة نموذج الاستماحة
                    </x-admin.print-button>
                </div>
            </x-slot>

            <div class="flex flex-col items-center text-center py-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20 mb-4">
                    <x-heroicon-o-check-badge style="width: 2rem; height: 2rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">تم إتمام جميع المتطلبات</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">يمكنك الآن طباعة نموذج الاستماحة وتقديمه للجهات المختصة.</p>
            </div>

            <div id="clearance-data"
                 data-student-name="{{ $student->user?->name }}"
                 data-enrollment-no="{{ $student->enrollment_no }}"
                 data-specialization="{{ $student->specialization?->name }}"
                 data-gpa="{{ number_format($student->gpa, 2) }}"
                 data-thesis-title="{{ $thesis?->title }}"
                 data-supervisor="{{ $thesis?->getSupervisorDisplayName() }}"
                 class="hidden"></div>
        </x-filament::section>
    @endif

    @script
    <script>
        window.printClearanceForm = function() {
            const el = document.getElementById('clearance-data');
            if (!el) return;

            const name   = el.getAttribute('data-student-name');
            const enrNo  = el.getAttribute('data-enrollment-no');
            const spec   = el.getAttribute('data-specialization');
            const gpa    = el.getAttribute('data-gpa');
            const thesis = el.getAttribute('data-thesis-title');
            const supervisor = el.getAttribute('data-supervisor');
            const now    = new Date();
            const date   = now.toLocaleDateString('en-CA');

            const html = '<!DOCTYPE html>'
                + '<html dir="rtl" lang="ar"><head><meta charset="UTF-8">'
                + '<title>نموذج الاستماحة — ' + name + '</title>'
                + '<style>'
                + '*{margin:0;padding:0;box-sizing:border-box}'
                + 'body{font-family:Arial,Tahoma,sans-serif;padding:40px;direction:rtl;color:#111}'
                + '.hdr{text-align:center;border-bottom:2px solid #111;padding-bottom:16px;margin-bottom:24px}'
                + '.hdr h1{font-size:15px;color:#444;margin-bottom:2px}'
                + '.hdr h2{font-size:20px;margin-bottom:12px}'
                + '.info{display:grid;grid-template-columns:1fr 1fr;gap:12px 32px;font-size:13px;margin-bottom:24px;text-align:right}'
                + '.info .item{display:flex;gap:6px;align-items:baseline}'
                + '.info .label{color:#666;white-space:nowrap}'
                + '.info .value{font-weight:bold}'
                + '.section{margin-top:24px;border:1px solid #999;border-radius:6px;padding:16px}'
                + '.section h3{font-size:15px;color:#1F4E79;margin-bottom:12px;border-bottom:1px solid #ddd;padding-bottom:8px}'
                + '.checklist{list-style:none;font-size:13px}'
                + '.checklist li{padding:8px 0;border-bottom:1px solid #eee;display:flex;align-items:center;gap:8px}'
                + '.checklist li:last-child{border-bottom:none}'
                + '.box{display:inline-block;width:14px;height:14px;border:1.5px solid #666;border-radius:2px;flex-shrink:0}'
                + '.sig-row{display:flex;justify-content:space-between;margin-top:60px;font-size:12px}'
                + '.sig{text-align:center;min-width:180px}'
                + '.sig .line{border-top:1px solid #333;margin-top:40px;padding-top:6px}'
                + '.ft{margin-top:32px;font-size:10px;color:#999;border-top:1px solid #ddd;padding-top:8px;display:flex;justify-content:space-between}'
                + '</style></head><body>'
                + '<div class="hdr">'
                + '<h1>كلية التقنية الإلكترونية — طرابلس</h1>'
                + '<h2>نموذج الاستماحة</h2>'
                + '</div>'
                + '<div class="info">'
                + '<div class="item"><span class="label">اسم الطالب:</span><span class="value">' + name + '</span></div>'
                + '<div class="item"><span class="label">رقم القيد:</span><span class="value">' + enrNo + '</span></div>'
                + '<div class="item"><span class="label">التخصص:</span><span class="value">' + spec + '</span></div>'
                + '<div class="item"><span class="label">المعدل التراكمي:</span><span class="value">' + gpa + '</span></div>'
                + (thesis ? '<div class="item"><span class="label">عنوان الرسالة:</span><span class="value">' + thesis + '</span></div>' : '')
                + (supervisor ? '<div class="item"><span class="label">المشرف:</span><span class="value">' + supervisor + '</span></div>' : '')
                + '</div>'
                + '<div class="section">'
                + '<h3>الجهات المطلوب الاستماحة منها</h3>'
                + '<ul class="checklist">'
                + '<li><span class="box"></span> المكتبة</li>'
                + '<li><span class="box"></span> القسم الأكاديمي</li>'
                + '<li><span class="box"></span> شؤون الطلاب</li>'
                + '<li><span class="box"></span> الشؤون المالية</li>'
                + '<li><span class="box"></span> تقنية المعلومات</li>'
                + '</ul>'
                + '</div>'
                + '<div class="sig-row">'
                + '<div class="sig"><div class="line">رئيس القسم</div></div>'
                + '<div class="sig"><div class="line">مدير مكتب الدراسات العليا</div></div>'
                + '</div>'
                + '<div class="ft">'
                + '<span>مكتب الدراسات العليا</span>'
                + '<span>التاريخ: ' + date + '</span>'
                + '</div>'
                + '</body></html>';

            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
            w.onload = function() { w.print(); };
        };
    </script>
    @endscript
</x-filament-panels::page>
