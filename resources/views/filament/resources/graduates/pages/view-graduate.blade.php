<x-filament-panels::page>
    @php
        $discussion = $this->record;
        $reg = $discussion->thesisRegistration;
        $student = $discussion->student;
        $user = $student->user;
        $supervisorDetails = $this->getSupervisorDetails();
        $completionDate = $this->getCoursesCompletionDate();
        $isGraduated = $student->status === \App\Enums\StudentStatus::Graduated;
    @endphp

    {{-- Status Badge --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <span @class([
            'inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold',
            'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $isGraduated,
            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => ! $isGraduated,
        ])>{{ $isGraduated ? 'متخرج' : 'تم التجليد والتسليم — بانتظار الاستماحة' }}</span>
        <span class="text-sm text-gray-400 dark:text-gray-500">تاريخ التجليد والتسليم: {{ $discussion->completed_at?->format('Y/m/d') ?? '—' }}</span>
    </div>

    {{-- Section 1: Student Info --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                <span>بيانات الطالب</span>
            </div>
        </x-slot>

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">اسم الطالب</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم القيد</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->enrollment_no ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">القسم</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->specialization?->name ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الرقم الوطني</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->national_id ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم الهاتف</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $user->phone ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">البريد الالكتروني</dt>
                <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $user->email }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المعدل التراكمي</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ number_format((float) $student->gpa, 2) }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ بداية التسجيل</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->created_at->format('Y/m/d') }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ الانتهاء من المقررات</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $completionDate ?? '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Section 2: Thesis --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-document-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                <span>الرسالة</span>
            </div>
        </x-slot>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">عنوان الرسالة (عربي)</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $reg->title }}</dd>
            </div>
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">عنوان الرسالة (إنجليزي)</dt>
                <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $reg->title_en ?? '—' }}</dd>
            </div>
            @if($supervisorDetails)
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10 sm:col-span-2">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المشرف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['name'] }} <span class="text-xs text-gray-400">({{ $supervisorDetails['type'] }})</span></dd>
                </div>
            @endif
        </dl>
    </x-filament::section>

    {{-- Print data container (hidden, used by JS) --}}
    <div id="degree-data"
         data-student-name="{{ $user->name }}"
         data-enrollment-no="{{ $student->enrollment_no }}"
         data-specialization="{{ $student->specialization?->name }}"
         data-gpa="{{ number_format((float) $student->gpa, 2) }}"
         data-thesis-title="{{ $reg->title }}"
         data-thesis-title-en="{{ $reg->title_en }}"
         data-supervisor="{{ $supervisorDetails['name'] ?? '' }}"
         data-graduation-date="{{ now()->format('Y/m/d') }}"
         class="hidden"></div>

    {{-- Back link --}}
    <a href="{{ url('/admin/graduates') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة للخريجون
    </a>

    @script
    <script>
        $wire.on('print-degree', () => {
            const el = document.getElementById('degree-data');
            if (!el) return;

            const name      = el.getAttribute('data-student-name');
            const enrNo     = el.getAttribute('data-enrollment-no');
            const spec      = el.getAttribute('data-specialization');
            const gpa       = el.getAttribute('data-gpa');
            const thesis    = el.getAttribute('data-thesis-title');
            const thesisEn  = el.getAttribute('data-thesis-title-en');
            const supervisor= el.getAttribute('data-supervisor');
            const now       = new Date();
            const date      = now.toLocaleDateString('en-CA');

            const html = '<!DOCTYPE html>'
                + '<html dir="rtl" lang="ar"><head><meta charset="UTF-8">'
                + '<title>إفادة تخرج — ' + name + '</title>'
                + '<style>'
                + '*{margin:0;padding:0;box-sizing:border-box}'
                + 'body{font-family:Arial,Tahoma,sans-serif;padding:40px;direction:rtl;color:#111}'
                + '.hdr{text-align:center;border-bottom:2px solid #111;padding-bottom:16px;margin-bottom:32px}'
                + '.hdr h1{font-size:15px;color:#444;margin-bottom:2px}'
                + '.hdr h2{font-size:22px;margin-bottom:4px}'
                + '.hdr h3{font-size:16px;color:#1F4E79}'
                + '.body-text{font-size:15px;line-height:2.2;text-align:justify;margin-bottom:32px}'
                + '.body-text .val{font-weight:bold;text-decoration:underline}'
                + '.info{display:grid;grid-template-columns:1fr 1fr;gap:12px 32px;font-size:13px;margin-bottom:32px;text-align:right}'
                + '.info .item{display:flex;gap:6px;align-items:baseline}'
                + '.info .label{color:#666;white-space:nowrap}'
                + '.info .value{font-weight:bold}'
                + '.sig-row{display:flex;justify-content:space-between;margin-top:60px;font-size:12px}'
                + '.sig{text-align:center;min-width:180px}'
                + '.sig .line{border-top:1px solid #333;margin-top:40px;padding-top:6px}'
                + '.ft{margin-top:32px;font-size:10px;color:#999;border-top:1px solid #ddd;padding-top:8px;display:flex;justify-content:space-between}'
                + '</style></head><body>'
                + '<div class="hdr">'
                + '<h1>كلية التقنية الإلكترونية — طرابلس</h1>'
                + '<h2>مكتب الدراسات العليا</h2>'
                + '<h3>إفادة تخرج</h3>'
                + '</div>'
                + '<div class="body-text">'
                + 'يفيد مكتب الدراسات العليا بكلية التقنية الإلكترونية — طرابلس بأن الطالب/ة '
                + '<span class="val">' + name + '</span>'
                + ' رقم القيد <span class="val">' + enrNo + '</span>'
                + ' قسم <span class="val">' + spec + '</span>'
                + ' قد أتم/ت بنجاح جميع متطلبات الحصول على درجة الماجستير'
                + ' بمعدل تراكمي <span class="val">' + gpa + '</span>'
                + (thesis ? ' وقد ناقش/ت رسالته/ا بعنوان:<br><span class="val">' + thesis + '</span>' : '')
                + (thesisEn ? '<br><span class="val" dir="ltr" style="unicode-bidi:embed">' + thesisEn + '</span>' : '')
                + (supervisor ? '<br>تحت إشراف: <span class="val">' + supervisor + '</span>' : '')
                + '.<br><br>'
                + 'وقد منح/ت هذه الإفادة بناءً على طلبه/ا لتقديمها لمن يهمه الأمر.'
                + '</div>'
                + '<div class="sig-row">'
                + '<div class="sig"><div class="line">رئيس القسم</div></div>'
                + '<div class="sig"><div class="line">مدير مكتب الدراسات العليا</div></div>'
                + '<div class="sig"><div class="line">عميد الكلية</div></div>'
                + '</div>'
                + '<div class="ft">'
                + '<span>مكتب الدراسات العليا — كلية التقنية الإلكترونية</span>'
                + '<span>التاريخ: ' + date + '</span>'
                + '</div>'
                + '</body></html>';

            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
            w.onload = function() { w.print(); };
        });
    </script>
    @endscript
</x-filament-panels::page>
