<x-filament-panels::page>
    @php
        $semester        = $this->getActiveSemester();
        $courses         = $this->getAvailableCourses();
        $max             = \App\Filament\Student\Pages\CourseSurveyPage::MAX_SELECTIONS;
        $selectedCount   = count($this->selections);
        $statuses        = $this->getEnrollmentStatuses();
        $surveySubmitted = $selectedCount > 0 && $semester && ! $semester->survey_open;
    @endphp

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
                            'border-danger-200 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10'     => $status === 'unavailable',
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
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'     => $status === 'unavailable',
                            ])>
                                @if($status === 'enrolled') مُسجَّل
                                @elseif($status === 'pending') قيد المراجعة
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

            {{-- Progress bar --}}
            <div class="mb-5">
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">تم اختيار {{ $selectedCount }} من {{ $max }}</span>
                    @if($selectedCount >= $max)
                        <span class="inline-flex items-center gap-1 font-semibold text-success-600 dark:text-success-400">
                            <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" />
                            الحد الأقصى
                        </span>
                    @elseif($selectedCount >= 1)
                        <span class="inline-flex items-center gap-1 font-semibold text-primary-600 dark:text-primary-400">
                            <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" />
                            يمكنك الحفظ أو إضافة المزيد
                        </span>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">اختر مقرراً واحداً على الأقل</span>
                    @endif
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                    <div class="h-2 rounded-full bg-primary-500 transition-all duration-300"
                         style="width: {{ ($selectedCount / $max) * 100 }}%"></div>
                </div>

                {{-- Priority slots --}}
                <div class="mt-3 flex gap-2">
                    @for($i = 1; $i <= $max; $i++)
                        @php $courseId = array_search($i, $this->selections); @endphp
                        <div @class([
                            'flex flex-1 items-center gap-2 rounded-lg border px-3 py-2 text-xs transition-all',
                            'border-primary-400 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300' => $courseId !== false,
                            'border-dashed border-gray-300 dark:border-white/20 text-gray-400 dark:text-gray-500' => $courseId === false,
                        ])>
                            <span @class([
                                'inline-flex size-5 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-primary-600 text-white dark:bg-primary-500' => $courseId !== false,
                                'bg-gray-200 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $courseId === false,
                            ])>{{ $i }}</span>
                            @if($courseId !== false)
                                <span class="truncate font-medium">
                                    {{ $courses->firstWhere('id', $courseId)?->name ?? '—' }}
                                </span>
                            @else
                                <span>فارغ</span>
                            @endif
                        </div>
                    @endfor
                </div>
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
                    @php $maxReached = $this->isMaxReached(); @endphp
                    @foreach($courses as $course)
                        @php
                            $selected = isset($this->selections[$course->id]);
                            $disabled = $maxReached && ! $selected;
                        @endphp
                        <div @class([
                            'flex items-center justify-between p-4 rounded-xl border transition-colors',
                            'border-primary-300 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10 cursor-pointer' => $selected,
                            'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-white/20 cursor-pointer' => ! $selected && ! $disabled,
                            'border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-gray-950 opacity-50 cursor-not-allowed' => $disabled,
                        ])>
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                    wire:click="toggleCourse({{ $course->id }})"
                                    @checked($selected)
                                    @disabled($disabled)
                                    class="fi-checkbox-input rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500 w-4 h-4 disabled:opacity-40 disabled:cursor-not-allowed"
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
