<x-filament-panels::page>
    @php
        $reg = $this->record;
        $student = $reg->student;
        $user = $student->user;
        $supervisorDetails = $this->getSupervisorDetails();
        $completionDate = $this->getCoursesCompletionDate();
        $statusLabel = $reg->status->getLabel();
        $statusColor = $reg->status->getColor();
    @endphp

    {{-- Status Badge --}}
    <div class="flex items-center justify-between">
        <span @class([
            'inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold',
            'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $statusColor === 'warning',
            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $statusColor === 'success',
            'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'   => $statusColor === 'danger',
        ])>{{ $statusLabel }}</span>
        <span class="text-sm text-gray-400 dark:text-gray-500">تاريخ التقديم: {{ $reg->created_at->format('Y/m/d') }}</span>
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
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">مكان وتاريخ الميلاد</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->birth_place ?? '—' }} — {{ $student->birth_date?->format('Y/m/d') ?? '—' }}</dd>
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
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">القسم</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->specialization?->name ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ بداية التسجيل</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->created_at->format('Y/m/d') }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ الانتهاء من المقررات</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $completionDate ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المعدل التراكمي</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ number_format((float) $student->gpa, 2) }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Section 2: Thesis Title --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-document-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                <span>عنوان الرسالة</span>
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
        </dl>
    </x-filament::section>

    {{-- Section 3: Supervisor --}}
    @if($supervisorDetails)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-academic-cap style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>بيانات المشرف</span>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                        'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400' => $supervisorDetails['type'] === 'من الجامعة',
                        'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $supervisorDetails['type'] === 'خارجي',
                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => $supervisorDetails['type'] === '—',
                    ])>{{ $supervisorDetails['type'] }}</span>
                </div>
            </x-slot>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الاسم</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['name'] }}</dd>
                </div>
                @if(isset($supervisorDetails['academic_rank']))
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الدرجة العلمية</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['academic_rank'] }}</dd>
                    </div>
                @endif
                @if(isset($supervisorDetails['qualification']))
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المؤهل العلمي</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['qualification'] }}</dd>
                    </div>
                @endif
                @if(isset($supervisorDetails['institution']))
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الجامعة / المعهد</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['institution'] }}</dd>
                    </div>
                @endif
                @if($supervisorDetails['email'] ?? null)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">البريد الالكتروني</dt>
                        <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $supervisorDetails['email'] }}</dd>
                    </div>
                @endif
                @if($supervisorDetails['phone'] ?? null)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">رقم الهاتف</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $supervisorDetails['phone'] }}</dd>
                    </div>
                @endif
            </dl>
        </x-filament::section>
    @endif

    {{-- Section 4: Proposal --}}
    @if($reg->proposal_path)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-paper-clip style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>المقترح البحثي</span>
                </div>
            </x-slot>

            <a href="{{ asset('storage/' . $reg->proposal_path) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:bg-gray-100 dark:hover:bg-white/10 transition">
                <x-heroicon-o-document-arrow-down style="width: 1.25rem; height: 1.25rem;" />
                تحميل ملف المقترح
            </a>
        </x-filament::section>
    @endif

    {{-- Rejection reason (if rejected) --}}
    @if($reg->status === \App\Enums\ThesisStatus::Rejected && $reg->rejection_reason)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-x-circle style="width: 1.1rem; height: 1.1rem;" class="text-danger-500" />
                    <span>سبب الرفض</span>
                </div>
            </x-slot>

            <div class="p-4 rounded-xl bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/30 text-sm text-danger-700 dark:text-danger-400">
                {{ $reg->rejection_reason }}
            </div>
        </x-filament::section>
    @endif

    {{-- Admin decision info --}}
    @if($reg->decided_by)
        <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-4">
            <span>القرار بواسطة: {{ $reg->decidedBy?->name ?? '—' }}</span>
            <span>بتاريخ: {{ $reg->decided_at?->format('Y/m/d H:i') ?? '—' }}</span>
        </div>
    @endif

    {{-- Back link --}}
    <a href="{{ url('/admin/thesis-registrations') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لتسجيلات الرسائل
    </a>
</x-filament-panels::page>
