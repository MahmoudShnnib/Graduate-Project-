<x-filament-panels::page>
    @php
        $student = $this->getStudent();
        $semester = $this->getActiveSemester();
    @endphp

    @if(! $student)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-user style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    لا يوجد سجل طالب مرتبط بهذا الحساب. يرجى التواصل مع الإدارة.
                </p>
            </div>
        </x-filament::section>
    @else
        {{-- Telegram linking --}}
        @if($this->telegramLinkingEnabled())
            <x-filament::section>
                <div @if($this->telegramDeepLink && ! $this->isTelegramLinked()) wire:poll.6s @endif>
                    @if($this->isTelegramLinked())
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <x-heroicon-s-check-badge class="w-6 h-6 text-success-500" />
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">حساب تيليجرام مربوط</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ستصلك إشعارات الدراسات العليا على تيليجرام.</p>
                                </div>
                            </div>
                            <x-filament::button color="gray" size="sm" wire:click="unlinkTelegram"
                                wire:confirm="هل تريد إلغاء ربط حساب تيليجرام؟">
                                إلغاء الربط
                            </x-filament::button>
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-bell-alert class="w-6 h-6 text-sky-500" />
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">استلم إشعاراتك عبر تيليجرام</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">اربط حسابك لاستلام إشعارات القبول والدرجات وغيرها على تيليجرام مجاناً.</p>
                                </div>
                            </div>
                            @if($this->telegramDeepLink)
                                <ol class="list-none space-y-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                    <li>١. اضغط «فتح بوت تيليجرام» بالأسفل.</li>
                                    <li>٢. داخل تيليجرام اضغط «Start / تشغيل».</li>
                                    <li>٣. سيظهر «✓ مربوط» هنا تلقائياً — أبقِ الصفحة مفتوحة.</li>
                                </ol>
                                <div class="flex items-center gap-3">
                                    <x-filament::button tag="a" href="{{ $this->telegramDeepLink }}" target="_blank" rel="noopener" color="info" size="sm">
                                        فتح بوت تيليجرام
                                    </x-filament::button>
                                    <span class="text-xs text-gray-400">⏳ في انتظار الربط...</span>
                                </div>
                            @else
                                <div>
                                    <x-filament::button color="info" size="sm" wire:click="startTelegramLink">
                                        ربط حساب تيليجرام
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Dismissal Banner --}}
        @if($student->status === \App\Enums\StudentStatus::Dismissed)
            @php $dismissalReason = $this->getDismissalReasonText(); @endphp
            <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-danger-300 bg-danger-50 dark:bg-danger-500/10 dark:border-danger-500/40 ring-2 ring-danger-200 dark:ring-danger-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                    <x-heroicon-o-shield-exclamation style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                </div>
                <div class="flex-1">
                    <p class="font-bold text-danger-800 dark:text-danger-300 text-sm">تم فصلك من الدراسة</p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-1">السبب: {{ $dismissalReason }}</p>
                    <p class="text-sm text-danger-600 dark:text-danger-500 mt-1">يمكنك تقديم طلب استثنائي من صفحة <a href="{{ route('filament.student.pages.exceptional-request-page') }}" class="underline font-semibold hover:text-danger-800 dark:hover:text-danger-300">الطلب الاستثنائي</a>.</p>
                    @if($student->dismissed_at)
                        <p class="text-xs text-danger-500 dark:text-danger-500 mt-2">{{ $student->dismissed_at->format('Y-m-d') }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Summon Banner --}}
        @php $summon = $this->getActiveSummon(); @endphp
        @if($summon)
            <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-danger-300 bg-danger-50 dark:bg-danger-500/10 dark:border-danger-500/40 ring-2 ring-danger-200 dark:ring-danger-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                    <x-heroicon-o-megaphone style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                </div>
                <div class="flex-1">
                    <p class="font-bold text-danger-800 dark:text-danger-300 text-sm">رسالة عاجلة من الإدارة — تم استدعاؤك</p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-1">{{ $summon->summon_message }}</p>
                    <p class="text-xs text-danger-500 dark:text-danger-500 mt-2">{{ $summon->summon_sent_at?->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        @endif

        {{-- Thesis Approved — Prompt to Request Discussion --}}
        @if($this->isThesisApprovedWithoutDiscussion())
            <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-success-300 bg-success-50 dark:bg-success-500/10 dark:border-success-500/40 ring-2 ring-success-200 dark:ring-success-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                    <x-heroicon-o-check-badge style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <div class="flex-1">
                    <p class="font-bold text-success-800 dark:text-success-300 text-sm">رسالتك مقبولة — يمكنك طلب المناقشة</p>
                    <p class="text-sm text-success-700 dark:text-success-400 mt-1">تم قبول رسالتك العلمية. يمكنك الآن تقديم طلب المناقشة من صفحة <a href="{{ route('filament.student.pages.thesis-registration-page') }}" class="underline font-semibold hover:text-success-900 dark:hover:text-success-300">تسجيل الرسالة</a>.</p>
                </div>
            </div>
        @endif

        {{-- Discussion Request Banner --}}
        @php $activeDiscussion = $this->getActiveDiscussionRequest(); @endphp
        @if($activeDiscussion)
            @if($activeDiscussion->status === \App\Enums\DiscussionStatus::Pending)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-warning-300 bg-warning-50 dark:bg-warning-500/10 dark:border-warning-500/40 ring-2 ring-warning-200 dark:ring-warning-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                        <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-warning-800 dark:text-warning-300 text-sm">طلب المناقشة قيد المراجعة</p>
                        <p class="text-sm text-warning-700 dark:text-warning-400 mt-1">تم إرسال طلب المناقشة وهو بانتظار مراجعة الإدارة. يمكنك متابعة حالة الطلب من صفحة <a href="{{ route('filament.student.pages.thesis-registration-page') }}" class="underline font-semibold hover:text-warning-900 dark:hover:text-warning-300">تسجيل الرسالة</a>.</p>
                    </div>
                </div>
            @elseif($activeDiscussion->status === \App\Enums\DiscussionStatus::Scheduled)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-info-300 bg-info-50 dark:bg-info-500/10 dark:border-info-500/40 ring-2 ring-info-200 dark:ring-info-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-info-100 dark:bg-info-500/20">
                        <x-heroicon-o-calendar-days style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-info-800 dark:text-info-300 text-sm">موعد المناقشة</p>
                        <p class="text-sm text-info-700 dark:text-info-400 mt-1">تم تحديد موعد مناقشة رسالتك: <span class="font-bold">{{ $activeDiscussion->discussion_date->format('Y/m/d') }}</span></p>
                    </div>
                </div>
            @elseif($activeDiscussion->status === \App\Enums\DiscussionStatus::Discussed)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-info-300 bg-info-50 dark:bg-info-500/10 dark:border-info-500/40 ring-2 ring-info-200 dark:ring-info-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-info-100 dark:bg-info-500/20">
                        <x-heroicon-o-chat-bubble-left-right style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-info-800 dark:text-info-300 text-sm">تمت المناقشة — بانتظار النتيجة</p>
                        <p class="text-sm text-info-700 dark:text-info-400 mt-1">تمت مناقشة رسالتك وجارٍ تحديد النتيجة من قبل الإدارة.</p>
                    </div>
                </div>
            @elseif($activeDiscussion->status === \App\Enums\DiscussionStatus::AcceptedWithEdits)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-warning-300 bg-warning-50 dark:bg-warning-500/10 dark:border-warning-500/40 ring-2 ring-warning-200 dark:ring-warning-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                        <x-heroicon-o-pencil-square style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-warning-800 dark:text-warning-300 text-sm">مقبول مع تعديلات</p>
                        <p class="text-sm text-warning-700 dark:text-warning-400 mt-1">تم قبول مناقشتك مع طلب تعديلات — بانتظار إتمام التعديلات.</p>
                    </div>
                </div>
            @elseif($activeDiscussion->status === \App\Enums\DiscussionStatus::DiscussionApproved)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-success-300 bg-success-50 dark:bg-success-500/10 dark:border-success-500/40 ring-2 ring-success-200 dark:ring-success-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                        <x-heroicon-o-check-badge style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-success-800 dark:text-success-300 text-sm">تمت الموافقة على المناقشة</p>
                        <p class="text-sm text-success-700 dark:text-success-400 mt-1">بانتظار التجليد والتسليم.</p>
                    </div>
                </div>
            @elseif($activeDiscussion->status === \App\Enums\DiscussionStatus::Completed)
                <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-success-300 bg-success-50 dark:bg-success-500/10 dark:border-success-500/40 ring-2 ring-success-200 dark:ring-success-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                        <x-heroicon-o-check-badge style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-success-800 dark:text-success-300 text-sm">تمت الموافقة النهائية</p>
                        <p class="text-sm text-success-700 dark:text-success-400 mt-1">تم التجليد والتسليم — يمكنك الآن تقديم طلب الاستماحة من صفحة <a href="{{ route('filament.student.pages.clearance-page') }}" class="underline font-semibold hover:text-success-900 dark:hover:text-success-300">الاستماحة</a>.</p>
                    </div>
                </div>
            @endif
        @endif

        {{-- Welcome Banner --}}
        <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-500/10 px-6 py-5">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-600 dark:bg-primary-500">
                        <x-heroicon-o-user style="width: 1.5rem; height: 1.5rem;" class="text-white" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">مرحباً، {{ auth()->user()->name }}</h2>
                        <p class="text-sm text-primary-700 dark:text-primary-300 mt-0.5">{{ $student->specialization?->name ?? 'بوابة الطالب الجامعي' }}</p>
                    </div>
                </div>
                @if($semester)
                    <div class="flex items-center gap-2 text-sm flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-900 border border-primary-200 dark:border-primary-500/30 text-primary-700 dark:text-primary-300 font-medium">
                            <x-heroicon-o-calendar-days style="width: 1rem; height: 1rem;" />
                            {{ $semester->name }}
                        </span>
                        @if($semester->survey_open)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-success-100 dark:bg-success-500/20 border border-success-200 dark:border-success-500/30 text-success-700 dark:text-success-400 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse flex-shrink-0"></span>
                                استطلاع مفتوح
                            </span>
                        @endif
                        @if($semester->isAddDropOpen())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-success-100 dark:bg-success-500/20 border border-success-200 dark:border-success-500/30 text-success-700 dark:text-success-400 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse flex-shrink-0"></span>
                                الإضافة والحذف مفتوحة
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Stat Cards --}}
        @php
            $isDismissed = $student->status === \App\Enums\StudentStatus::Dismissed;
            $hasWarnings = $student->warnings_count > 0;
            $activeWarnings = $this->getActiveWarnings();
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/20">
                    <x-heroicon-o-star style="width: 1.25rem; height: 1.25rem;" class="text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المعدل التراكمي</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 leading-none">{{ number_format((float) $student->gpa, 2) }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-success-100 dark:bg-success-500/20">
                    <x-heroicon-o-academic-cap style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المقررات المجتازة</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $student->completedCoursesCount() }}</p>
                </div>
            </div>

            {{-- Warnings Card --}}
            <div @class([
                'rounded-xl border p-5',
                'border-danger-300 dark:border-danger-500/40 bg-danger-50 dark:bg-danger-500/10 ring-1 ring-danger-200 dark:ring-danger-500/30 cursor-pointer hover:ring-2' => $isDismissed,
                'border-warning-300 dark:border-warning-500/40 bg-warning-50 dark:bg-warning-500/10 ring-1 ring-warning-200 dark:ring-warning-500/30 cursor-pointer hover:ring-2' => $hasWarnings && ! $isDismissed,
                'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900' => ! $hasWarnings && ! $isDismissed,
            ])
                @if($hasWarnings || $isDismissed)
                    x-on:click="$dispatch('open-modal', { id: 'warnings-modal' })"
                @endif
            >
                <div class="flex items-center gap-4">
                    <div @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                        'bg-danger-100 dark:bg-danger-500/20' => $isDismissed,
                        'bg-warning-100 dark:bg-warning-500/20' => $hasWarnings && ! $isDismissed,
                        'bg-gray-100 dark:bg-white/10' => ! $hasWarnings && ! $isDismissed,
                    ])>
                        <x-heroicon-o-exclamation-triangle style="width: 1.25rem; height: 1.25rem;" @class([
                            'text-danger-600 dark:text-danger-400' => $isDismissed,
                            'text-warning-600 dark:text-warning-400' => $hasWarnings && ! $isDismissed,
                            'text-gray-400 dark:text-gray-500' => ! $hasWarnings && ! $isDismissed,
                        ]) />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">التحذيرات</p>
                        @if($isDismissed)
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-danger-700 dark:text-danger-400">
                                <span class="w-2 h-2 rounded-full bg-danger-500 animate-pulse flex-shrink-0"></span>
                                لديك تحذيرات
                            </span>
                        @elseif($hasWarnings)
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-warning-700 dark:text-warning-400">
                                <span class="w-2 h-2 rounded-full bg-warning-500 animate-pulse flex-shrink-0"></span>
                                لديك تحذير
                            </span>
                        @else
                            <p class="text-sm font-medium text-gray-400 dark:text-gray-500 leading-none">لا يوجد</p>
                        @endif
                    </div>
                </div>
            </div>

            @php
                $displayLabel = $student->getDisplayStatusLabel();
                $displayColor = $student->getDisplayStatusColor();
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                    'bg-success-100 dark:bg-success-500/20' => $displayColor === 'success',
                    'bg-danger-100 dark:bg-danger-500/20' => $displayColor === 'danger',
                    'bg-warning-100 dark:bg-warning-500/20' => $displayColor === 'warning',
                    'bg-info-100 dark:bg-info-500/20' => $displayColor === 'info',
                    'bg-gray-100 dark:bg-white/10' => $displayColor === 'gray',
                ])>
                    <x-heroicon-o-identification style="width: 1.25rem; height: 1.25rem;" @class([
                        'text-success-600 dark:text-success-400' => $displayColor === 'success',
                        'text-danger-600 dark:text-danger-400' => $displayColor === 'danger',
                        'text-warning-600 dark:text-warning-400' => $displayColor === 'warning',
                        'text-info-600 dark:text-info-400' => $displayColor === 'info',
                        'text-gray-400 dark:text-gray-500' => $displayColor === 'gray',
                    ]) />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الحالة</p>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold',
                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $displayColor === 'success',
                        'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $displayColor === 'danger',
                        'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $displayColor === 'warning',
                        'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $displayColor === 'info',
                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => $displayColor === 'gray',
                    ])>{{ $displayLabel }}</span>
                </div>
            </div>
        </div>

        {{-- Student Info + Semester --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-user-circle style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                            <span>بياناتي</span>
                        </div>
                    </x-slot>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">الاسم</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">رقم القيد</dt>
                            <dd class="font-semibold font-mono text-gray-900 dark:text-white">{{ $student->enrollment_no ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">التخصص</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $student->specialization?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">البريد الإلكتروني</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ auth()->user()->email }}</dd>
                        </div>
                    </dl>
                </x-filament::section>
            </div>

            <div>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar-days style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                            <span>الفصل الدراسي الحالي</span>
                        </div>
                    </x-slot>
                    @if($semester)
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">الفصل</dt>
                                <dd class="font-semibold text-gray-900 dark:text-white">{{ $semester->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 mb-0.5 text-xs uppercase tracking-wide">فترة الإضافة والحذف</dt>
                                <dd class="mt-1">
                                    @if($semester->isAddDropOpen())
                                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-success-600 dark:text-success-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success-500 flex-shrink-0"></span>
                                            مفتوحة
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400 dark:text-gray-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                            مغلقة
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">لا يوجد فصل دراسي نشط حالياً</p>
                    @endif
                </x-filament::section>
            </div>
        </div>

        {{-- Quick Links --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-squares-2x2 style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>الخدمات الإلكترونية</span>
                </div>
            </x-slot>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('filament.student.pages.course-survey-page') }}" wire:navigate
                   class="group flex flex-col items-center gap-3 p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-500/10 dark:hover:border-primary-500/40 transition-all text-center">
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 shadow-sm group-hover:border-primary-200 group-hover:bg-primary-50 dark:group-hover:border-primary-500/30 dark:group-hover:bg-primary-500/10 transition-all">
                        <x-heroicon-o-clipboard-document-list style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" class="text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">استطلاع المقررات</span>
                </a>

                <a href="{{ route('filament.student.pages.student-courses-page') }}" wire:navigate
                   class="group flex flex-col items-center gap-3 p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-500/10 dark:hover:border-primary-500/40 transition-all text-center">
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 shadow-sm group-hover:border-primary-200 group-hover:bg-primary-50 dark:group-hover:border-primary-500/30 dark:group-hover:bg-primary-500/10 transition-all">
                        <x-heroicon-o-academic-cap style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" class="text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">مقرراتي</span>
                </a>

                <a href="{{ route('filament.student.pages.grades-page') }}" wire:navigate
                   class="group flex flex-col items-center gap-3 p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-500/10 dark:hover:border-primary-500/40 transition-all text-center">
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 shadow-sm group-hover:border-primary-200 group-hover:bg-primary-50 dark:group-hover:border-primary-500/30 dark:group-hover:bg-primary-500/10 transition-all">
                        <x-heroicon-o-chart-bar style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" class="text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">الدرجات</span>
                </a>

                <a href="{{ route('filament.student.pages.thesis-registration-page') }}" wire:navigate
                   class="group flex flex-col items-center gap-3 p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 hover:bg-primary-50 hover:border-primary-300 dark:hover:bg-primary-500/10 dark:hover:border-primary-500/40 transition-all text-center">
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 shadow-sm group-hover:border-primary-200 group-hover:bg-primary-50 dark:group-hover:border-primary-500/30 dark:group-hover:bg-primary-500/10 transition-all">
                        <x-heroicon-o-document-text style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" class="text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">تسجيل الرسالة</span>
                </a>
            </div>
        </x-filament::section>

        {{-- Warnings Modal --}}
        @if($hasWarnings || $isDismissed)
            @php $failCounts = $this->getCourseFailCounts(); @endphp
            <x-filament::modal id="warnings-modal" width="md">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle style="width: 1.1rem; height: 1.1rem;" class="{{ $isDismissed ? 'text-danger-500' : 'text-warning-500' }}" />
                        <span>تفاصيل التحذيرات</span>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @foreach($activeWarnings as $warning)
                        <div @class([
                            'flex items-start gap-3 p-3 rounded-lg border text-sm',
                            'border-danger-200 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10' => $isDismissed || $warning->has_exceptional_chance,
                            'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => ! $isDismissed && ! $warning->has_exceptional_chance,
                        ])>
                            @if($warning->type === \App\Enums\WarningType::CourseFailure)
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ ($isDismissed || $warning->has_exceptional_chance) ? 'bg-danger-100 dark:bg-danger-500/20' : 'bg-warning-100 dark:bg-warning-500/20' }}">
                                    <x-heroicon-o-x-circle style="width: 1rem; height: 1rem;" class="{{ ($isDismissed || $warning->has_exceptional_chance) ? 'text-danger-500' : 'text-warning-500' }}" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="font-semibold {{ ($isDismissed || $warning->has_exceptional_chance) ? 'text-danger-800 dark:text-danger-300' : 'text-warning-800 dark:text-warning-300' }}">{{ $warning->course?->name ?? 'غير محددة' }}</p>
                                        @php $count = $failCounts[$warning->course_id] ?? 1; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ ($isDismissed || $warning->has_exceptional_chance) ? 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' : 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' }}">
                                            رسوب {{ $count === 1 ? 'مرة واحدة' : ($count === 2 ? 'مرتين' : $count . ' مرات') }}
                                        </span>
                                    </div>
                                    @if($warning->has_exceptional_chance)
                                        <div class="mt-2 p-2 rounded-md bg-danger-100 dark:bg-danger-500/20 border border-danger-300 dark:border-danger-500/40">
                                            <p class="text-xs font-bold text-danger-800 dark:text-danger-300 flex items-center gap-1.5">
                                                <x-heroicon-o-shield-exclamation style="width: 0.875rem; height: 0.875rem;" class="flex-shrink-0" />
                                                هذه فرصتك الأخيرة في هذه المادة
                                            </p>
                                            <p class="text-xs text-danger-700 dark:text-danger-400 mt-0.5">في حال الرسوب مرة أخرى سيتم فصلك نهائياً من الدراسة.</p>
                                        </div>
                                    @else
                                        <p class="text-xs {{ $isDismissed ? 'text-danger-600 dark:text-danger-400' : 'text-warning-600 dark:text-warning-400' }} mt-1">السبب: رسوب في مادة</p>
                                    @endif
                                </div>
                            @else
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isDismissed ? 'bg-danger-100 dark:bg-danger-500/20' : 'bg-warning-100 dark:bg-warning-500/20' }}">
                                    <x-heroicon-o-clock style="width: 1rem; height: 1rem;" class="{{ $isDismissed ? 'text-danger-500' : 'text-warning-500' }}" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold {{ $isDismissed ? 'text-danger-800 dark:text-danger-300' : 'text-warning-800 dark:text-warning-300' }}">انقطاع متتالي عن الدراسة</p>
                                    <p class="text-xs {{ $isDismissed ? 'text-danger-600 dark:text-danger-400' : 'text-warning-600 dark:text-warning-400' }} mt-1">السبب: انقطاع — {{ $warning->semester?->name ?? '' }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::modal>
        @endif
    @endif
</x-filament-panels::page>
