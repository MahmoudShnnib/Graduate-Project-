<x-filament-panels::page>
    @php
        $semester        = $this->getActiveSemester();
        $courses         = $this->getAvailableCourses();
        $selectedCount   = count($this->selections);
        $statuses        = $this->getEnrollmentStatuses();
        $surveySubmitted = $selectedCount > 0 && $semester && ! $semester->survey_open;
        $student         = auth()->user()->student;
        $isSuspended     = $student && $semester && $student->isSuspendedInSemester($semester);
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    @if($isSuspended)
        <x-filament::section>
            <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                    <x-heroicon-o-pause-circle style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">قيدك موقوف لهذا الفصل</p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">تم قبول طلب إيقاف القيد. لا يمكنك تقديم اختيارات استطلاع المقررات لهذا الفصل. ستبقى بياناتك متاحة للعرض.</p>
                </div>
            </div>
        </x-filament::section>
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
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $course->code }} — {{ $course->credits }} ساعة</p>
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
            <x-slot name="description">اختر المقررات التي ترغب في دراستها هذا الفصل. يمكنك تعديل اختياراتك في أي وقت قبل إغلاق الاستطلاع.</x-slot>

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
                                        {{ $course->credits }} ساعة
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
                    <x-filament::button wire:click="submit" icon="heroicon-o-check">
                        حفظ الاختيارات
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
