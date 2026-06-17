<x-filament-panels::page>
    @php
        $semester        = $this->getActiveSemester();
        $courses         = $this->getAvailableCourses();
        $selectedCount   = count($this->selections);
        $statuses        = $this->getEnrollmentStatuses();
        $student         = auth()->user()->student;
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- No active semester --}}
    @if(! $semester)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-calendar style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد فصل دراسي نشط حالياً. يُرجى المراجعة لاحقاً.</p>
            </div>
        </x-filament::section>

    {{-- Survey closed — show submission status --}}
    @elseif(! $semester->survey_open)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>استطلاع المقررات</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                        {{ $semester->name }}
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">الاستطلاع مغلق حالياً. يُرجى انتظار نتائج التسجيل.</x-slot>

            @if($selectedCount === 0)
                <div class="py-6 text-center text-gray-500">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-lock-closed style="width: 1.5rem; height: 1.5rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p>لم تُرسل اختياراتك قبل إغلاق الاستطلاع.</p>
                </div>
            @else
                <p class="mb-4 text-sm text-gray-500">اختياراتك المسجلة — يمكنك متابعة حالة التسجيل أدناه:</p>
                <div class="space-y-2">
                    @foreach($courses->whereIn('id', array_keys($this->selections))->sortBy(fn($c) => $this->selections[$c->id]) as $course)
                        @php
                            $status = $statuses[$course->id] ?? 'pending';
                            $priority = $this->selections[$course->id];
                        @endphp
                        <div @class([
                            'flex items-center justify-between rounded-xl border p-3 transition-colors',
                            'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' => $status === 'enrolled',
                            'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => $status === 'pending',
                            'border-danger-200 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10'     => $status === 'unavailable' || $status === 'rejected',
                        ])>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-primary-600 dark:bg-primary-500 text-xs font-bold text-white">
                                    {{ $priority }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $course->code }} — {{ $course->credits }} وحدة</p>
                                </div>
                            </div>
                            <span @class([
                                'rounded-lg px-2.5 py-1 text-xs font-semibold',
                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $status === 'enrolled',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $status === 'pending',
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'     => $status === 'unavailable' || $status === 'rejected',
                            ])>
                                @if($status === 'enrolled') مُسجَّل
                                @elseif($status === 'pending') قيد المراجعة
                                @elseif($status === 'rejected') مرفوض
                                @else المادة غير متاحة هذا الفصل
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

    {{-- Survey open — allow selection --}}
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>استطلاع المقررات</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400">
                        {{ $semester->name }}
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">اختر المقررات التي ترغب في دراستها هذا الفصل بعناية. لا يمكنك تعديل اختياراتك بعد إرسالها.</x-slot>

            @php $alreadySubmitted = $this->hasSubmittedSurvey(); @endphp

            @if($alreadySubmitted)
                {{-- Locked: the survey was already submitted this semester — read-only --}}
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10 px-4 py-3">
                    <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" class="mt-0.5 shrink-0 text-success-600 dark:text-success-400" />
                    <div>
                        <p class="text-sm font-semibold text-success-800 dark:text-success-300">تم إرسال اختياراتك</p>
                        <p class="text-xs text-success-700 dark:text-success-400">لا يمكن تعديل الاستطلاع بعد إرساله. يمكنك متابعة حالة التسجيل أدناه.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach($courses->whereIn('id', array_keys($this->selections))->sortBy(fn($c) => $this->selections[$c->id]) as $course)
                        @php
                            $status = $statuses[$course->id] ?? 'pending';
                            $priority = $this->selections[$course->id];
                        @endphp
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-white/10 p-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-primary-600 dark:bg-primary-500 text-xs font-bold text-white">{{ $priority }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $course->code }} — {{ $course->credits }} وحدة</p>
                                </div>
                            </div>
                            <span @class([
                                'rounded-lg px-2.5 py-1 text-xs font-semibold',
                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $status === 'enrolled',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $status === 'pending',
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'     => $status === 'unavailable' || $status === 'rejected',
                            ])>
                                @if($status === 'enrolled') مُسجَّل
                                @elseif($status === 'pending') قيد المراجعة
                                @elseif($status === 'rejected') مرفوض
                                @else المادة غير متاحة هذا الفصل
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @else

            {{-- Selection summary --}}
            <div class="mb-5 flex items-center justify-between rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/60 dark:bg-white/5 px-4 py-3 text-sm">
                @if($selectedCount === 0)
                    <span class="text-gray-500 dark:text-gray-400">اختر المقررات التي تريد دراستها — يمكنك اختيار أي عدد تراه مناسباً.</span>
                @else
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        تم اختيار <strong class="text-gray-900 dark:text-white">{{ $selectedCount }}</strong> مقرر
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-information-circle style="width: 0.875rem; height: 0.875rem;" />
                        الأرقام تمثل ترتيب الأولوية
                    </span>
                @endif
            </div>

            {{-- Course list --}}
            @if($courses->isEmpty())
                <div class="text-center py-12">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد مقررات متاحة حالياً.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($courses as $course)
                        @php $selected = isset($this->selections[$course->id]); @endphp
                        <div @class([
                            'flex items-center justify-between p-4 rounded-xl border transition-colors cursor-pointer',
                            'border-primary-300 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10' => $selected,
                            'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-white/20' => ! $selected,
                        ])>
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                    wire:click="toggleCourse({{ $course->id }})"
                                    @checked($selected)
                                    class="fi-checkbox-input rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500 w-4 h-4"
                                />
                                <div>
                                    <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        <span class="font-mono">{{ $course->code }}</span>
                                        <span class="mx-1.5 text-gray-300 dark:text-gray-600">·</span>
                                        {{ $course->credits }} وحدة
                                        <span class="mx-1.5 text-gray-300 dark:text-gray-600">·</span>
                                        {{ $course->type?->label() }}
                                    </p>
                                </div>
                            </div>
                            @if($selected)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 dark:bg-primary-500 text-white text-xs font-bold flex-shrink-0">
                                    {{ $this->selections[$course->id] }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/10 flex justify-between items-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        تم اختيار <strong class="text-gray-900 dark:text-white">{{ count($this->selections) }}</strong> مقرر
                    </p>
                    <x-filament::button wire:click="confirmSubmit" icon="heroicon-o-check">
                        إرسال الاختيارات
                    </x-filament::button>
                </div>
            @endif
            @endif
        </x-filament::section>

        {{-- Retake section --}}
        @php
            $retakeableCourses = $this->getRetakeableCourses();
            $retakeRequests = $this->getRetakeRequests();
            $retakeRequestCourseIds = $retakeRequests->pluck('course_id')->all();
            $confirmCourseId = $this->retakeConfirmCourseId;
            $confirmCourse = $confirmCourseId ? $retakeableCourses->firstWhere('id', $confirmCourseId) : null;
        @endphp

        @if($retakeableCourses->isNotEmpty() || $retakeRequests->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-arrow-path style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                            <span>طلب إعادة مادة</span>
                        </div>
                        @if($retakeRequests->isNotEmpty())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                                {{ $retakeRequests->count() }} طلب
                            </span>
                        @endif
                    </div>
                </x-slot>
                <x-slot name="description">يمكنك طلب إعادة مادة سبق أن اجتزتها لتحسين تقديرك ومعدلك التراكمي.</x-slot>

                {{-- Existing retake requests with status --}}
                @if($retakeRequests->isNotEmpty())
                    <div class="space-y-2 mb-4">
                        @foreach($retakeRequests as $req)
                            @php
                                $isApproved = $req->is_processed && $req->rejected_at === null;
                                $isRejected = $req->rejected_at !== null;
                            @endphp
                            <div @class([
                                'flex items-center justify-between p-3 rounded-xl border',
                                'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' => $isApproved,
                                'border-danger-200 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10' => $isRejected,
                                'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => ! $isApproved && ! $isRejected,
                            ])>
                                <div class="flex items-center gap-3">
                                    <div @class([
                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                        'bg-success-100 dark:bg-success-500/20' => $isApproved,
                                        'bg-danger-100 dark:bg-danger-500/20' => $isRejected,
                                        'bg-warning-100 dark:bg-warning-500/20' => ! $isApproved && ! $isRejected,
                                    ])>
                                        @if($isApproved)
                                            <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" class="text-success-600 dark:text-success-400" />
                                        @elseif($isRejected)
                                            <x-heroicon-o-x-circle style="width: 1rem; height: 1rem;" class="text-danger-600 dark:text-danger-400" />
                                        @else
                                            <x-heroicon-o-arrow-path style="width: 1rem; height: 1rem;" class="text-warning-600 dark:text-warning-400" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $req->course->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $req->course->code }}</p>
                                    </div>
                                </div>
                                <span @class([
                                    'rounded-lg px-2.5 py-1 text-xs font-semibold',
                                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $isApproved,
                                    'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $isRejected,
                                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => ! $isApproved && ! $isRejected,
                                ])>
                                    @if($isApproved) تمت الموافقة
                                    @elseif($isRejected) مرفوض
                                    @else قيد المراجعة
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Available courses for retake --}}
                @if($semester->survey_open)
                    @php $availableRetakes = $retakeableCourses->whereNotIn('id', $retakeRequestCourseIds); @endphp
                    @if($availableRetakes->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($availableRetakes as $course)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10">
                                            <x-heroicon-o-book-open style="width: 1rem; height: 1rem;" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $course->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $course->code }} — {{ $course->credits }} وحدة</p>
                                        </div>
                                    </div>
                                    <x-filament::button
                                        wire:click="confirmRetake({{ $course->id }})"
                                        size="xs"
                                        color="warning"
                                        icon="heroicon-o-arrow-path"
                                    >
                                        طلب إعادة
                                    </x-filament::button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </x-filament::section>
        @endif

        {{-- Retake confirmation modal --}}
        @if($confirmCourse)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="cancelRetake">
                <div class="w-full max-w-md mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-warning-100 dark:bg-warning-500/20 mx-auto mb-4">
                            <x-heroicon-o-arrow-path style="width: 1.75rem; height: 1.75rem;" class="text-warning-600 dark:text-warning-400" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">طلب إعادة مادة</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">هل تريد تقديم طلب إعادة لمادة:</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white mb-1">{{ $confirmCourse->name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mb-4">{{ $confirmCourse->code }} — {{ $confirmCourse->credits }} وحدة</p>
                        <div class="rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 p-3 text-sm text-warning-800 dark:text-warning-300 mb-6 text-start">
                            <p class="font-medium mb-1">ملاحظة:</p>
                            <ul class="text-xs text-warning-700 dark:text-warning-400 space-y-1 list-disc list-inside">
                                <li>سيتم مراجعة طلبك من قبل الإدارة</li>
                                <li>في حال الموافقة، ستُحتسب الدرجة الجديدة في المعدل</li>
                                <li>الإعادة تُحتسب ضمن الحد الأقصى للمقررات في الفصل</li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10">
                        <x-filament::button wire:click="cancelRetake" color="gray" class="flex-1">
                            إلغاء
                        </x-filament::button>
                        <x-filament::button wire:click="submitRetake({{ $confirmCourse->id }})" color="warning" icon="heroicon-o-arrow-path" class="flex-1">
                            تأكيد الطلب
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Submit confirmation modal — submitting the survey is final --}}
        @if($this->confirmingSubmit)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="cancelSubmit">
                <div class="w-full max-w-md mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-warning-100 dark:bg-warning-500/20 mx-auto mb-4">
                            <x-heroicon-o-exclamation-triangle style="width: 1.75rem; height: 1.75rem;" class="text-warning-600 dark:text-warning-400" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">تأكيد إرسال الاستطلاع</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">أنت على وشك إرسال <strong class="text-gray-900 dark:text-white">{{ count($this->selections) }}</strong> مقرر.</p>
                        <div class="rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 p-3 text-sm text-warning-800 dark:text-warning-300 mt-4 text-start">
                            <p class="font-medium">تنبيه: لا يمكنك تعديل اختياراتك بعد الإرسال. تأكد من اختياراتك قبل المتابعة.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10">
                        <x-filament::button wire:click="cancelSubmit" color="gray" class="flex-1">
                            إلغاء
                        </x-filament::button>
                        <x-filament::button wire:click="submit" color="primary" icon="heroicon-o-check" class="flex-1">
                            تأكيد الإرسال
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
