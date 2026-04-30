<x-filament-panels::page>
    @php
        $discussion = $this->record;
        $reg = $discussion->thesisRegistration;
        $student = $discussion->student;
        $user = $student->user;
        $supervisorDetails = $this->getSupervisorDetails();
        $completionDate = $this->getCoursesCompletionDate();
        $statusLabel = $discussion->status->getLabel();
        $statusColor = $discussion->status->getColor();
    @endphp

    {{-- Status Badge + Discussion Date --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <span @class([
                'inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold',
                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $statusColor === 'warning',
                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $statusColor === 'success',
                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'   => $statusColor === 'danger',
                'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400'             => $statusColor === 'info',
            ])>{{ $statusLabel }}</span>
            @if($discussion->discussion_date)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400">
                    <x-heroicon-o-calendar-days style="width: 1rem; height: 1rem;" />
                    {{ $discussion->discussion_date->format('Y/m/d') }}
                </span>
            @endif
        </div>
        <span class="text-sm text-gray-400 dark:text-gray-500">تاريخ الطلب: {{ $discussion->created_at->format('Y/m/d') }}</span>
    </div>

    {{-- Phase Progress --}}
    @php
        $phases = [
            ['label' => 'المراجعة', 'done' => ! in_array($discussion->status, [\App\Enums\DiscussionStatus::Pending])],
            ['label' => 'المناقشة', 'done' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Discussed, \App\Enums\DiscussionStatus::DiscussionRejected, \App\Enums\DiscussionStatus::AcceptedWithEdits, \App\Enums\DiscussionStatus::DiscussionApproved, \App\Enums\DiscussionStatus::Completed])],
            ['label' => 'النتيجة', 'done' => in_array($discussion->status, [\App\Enums\DiscussionStatus::AcceptedWithEdits, \App\Enums\DiscussionStatus::DiscussionApproved, \App\Enums\DiscussionStatus::Completed])],
            ['label' => 'التجليد والتسليم', 'done' => $discussion->status === \App\Enums\DiscussionStatus::Completed],
        ];
        $isRejected = in_array($discussion->status, [\App\Enums\DiscussionStatus::Rejected, \App\Enums\DiscussionStatus::DiscussionRejected]);
    @endphp
    @if(! $isRejected)
        <div class="flex items-center gap-2">
            @foreach($phases as $i => $phase)
                <div @class([
                    'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium',
                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $phase['done'],
                    'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => ! $phase['done'],
                ])>
                    @if($phase['done'])
                        <x-heroicon-o-check-circle style="width: 0.875rem; height: 0.875rem;" class="flex-shrink-0" />
                    @else
                        <span class="w-3.5 h-3.5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex-shrink-0"></span>
                    @endif
                    {{ $phase['label'] }}
                </div>
                @if(! $loop->last)
                    <x-heroicon-o-chevron-left style="width: 0.75rem; height: 0.75rem;" class="text-gray-300 dark:text-gray-600 flex-shrink-0" />
                @endif
            @endforeach
        </div>
    @endif

    {{-- Rejection Notice --}}
    @if($isRejected && $discussion->rejection_reason)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-x-circle style="width: 1.1rem; height: 1.1rem;" class="text-danger-500" />
                    <span>سبب الرفض</span>
                </div>
            </x-slot>

            <div class="p-4 rounded-xl bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/30 text-sm text-danger-700 dark:text-danger-400">
                {{ $discussion->rejection_reason }}
            </div>
        </x-filament::section>
    @endif

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

    {{-- Admin decision info --}}
    @if($discussion->decided_by)
        <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-4">
            <span>آخر قرار بواسطة: {{ $discussion->decidedBy?->name ?? '—' }}</span>
            <span>بتاريخ: {{ $discussion->decided_at?->format('Y/m/d H:i') ?? '—' }}</span>
        </div>
    @endif

    {{-- Back link --}}
    <a href="{{ url('/admin/discussion-requests') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لطلبات المناقشة
    </a>
</x-filament-panels::page>
