<x-filament-panels::page>
    @php
        $student = $this->getStudent();
        $coursesByType = $this->getCoursesByType();
        $enrollmentMap = $this->getEnrollmentMap();

        $allCourses = $coursesByType->flatten();
        $totalCount = $allCourses->count();
        $specCourseIds = $allCourses->pluck('id')->all();
        $passedCount = collect($enrollmentMap)->filter(fn ($e, $id) => $e['status'] === 'passed' && in_array($id, $specCourseIds))->count();
        $currentCount = collect($enrollmentMap)->filter(fn ($e, $id) => $e['status'] === 'current' && in_array($id, $specCourseIds))->count();
        $remainingCount = $totalCount - $passedCount - $currentCount;

        $typeLabels = [
            'general' => 'المقررات العامة',
            'specialized' => 'المقررات التخصصية',
            'elective' => 'المقررات الاختيارية',
        ];
        $typeIcons = [
            'general' => 'heroicon-o-globe-alt',
            'specialized' => 'heroicon-o-beaker',
            'elective' => 'heroicon-o-sparkles',
        ];
        $typeOrder = ['general', 'specialized', 'elective'];
    @endphp

    @if(! $student)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-user style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">لا يوجد سجل طالب مرتبط بهذا الحساب.</p>
            </div>
        </x-filament::section>
    @elseif($totalCount === 0)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-map style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">لا توجد مقررات مسجلة لتخصصك حالياً.</p>
            </div>
        </x-filament::section>
    @else
        {{-- Progress Summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/20">
                    <x-heroicon-o-book-open style="width: 1.25rem; height: 1.25rem;" class="text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">إجمالي المقررات</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $totalCount }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-success-100 dark:bg-success-500/20">
                    <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">مقررات مجتازة</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400 leading-none">{{ $passedCount }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-info-100 dark:bg-info-500/20">
                    <x-heroicon-o-arrow-path style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">قيد الدراسة</p>
                    <p class="text-2xl font-bold text-info-600 dark:text-info-400 leading-none">{{ $currentCount }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-white/10">
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">متبقية</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $remainingCount }}</p>
                </div>
            </div>
        </div>

        {{-- Overall Progress Bar --}}
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">تقدم شجرة موادي</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $totalCount > 0 ? round(($passedCount / $totalCount) * 100) : 0 }}%</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10 flex">
                @if($passedCount > 0)
                    <div class="h-3 bg-success-500 transition-all duration-500" style="width: {{ ($passedCount / $totalCount) * 100 }}%"></div>
                @endif
                @if($currentCount > 0)
                    <div class="h-3 bg-info-500 transition-all duration-500" style="width: {{ ($currentCount / $totalCount) * 100 }}%"></div>
                @endif
            </div>
            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-success-500"></span> مجتازة</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-info-500"></span> قيد الدراسة</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span> متبقية</span>
            </div>
        </div>

        {{-- Course Sections by Type --}}
        @foreach($typeOrder as $type)
            @php $courses = $coursesByType->get($type, collect()); @endphp
            @if($courses->isNotEmpty())
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                @switch($type)
                                    @case('general')
                                        <x-heroicon-o-globe-alt style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                                        @break
                                    @case('specialized')
                                        <x-heroicon-o-beaker style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                                        @break
                                    @case('elective')
                                        <x-heroicon-o-sparkles style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                                        @break
                                @endswitch
                                <span>{{ $typeLabels[$type] ?? $type }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                                {{ $courses->count() }} مقرر
                            </span>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($courses as $course)
                            @php
                                $enrollment = $enrollmentMap[$course->id] ?? null;
                                $courseStatus = $enrollment['status'] ?? 'remaining';
                                $grade = $enrollment['grade'] ?? null;
                                $semesterName = $enrollment['semester_name'] ?? null;
                                $isExpanded = isset($this->expandedCourses[$course->id]);
                            @endphp

                            <div @class([
                                'rounded-xl border p-4 transition-all',
                                'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' => $courseStatus === 'passed',
                                'border-info-200 bg-info-50 dark:border-info-500/30 dark:bg-info-500/10' => $courseStatus === 'current',
                                'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => $courseStatus === 'remaining',
                            ])>
                                {{-- Course Header --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div @class([
                                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg mt-0.5',
                                            'bg-success-100 dark:bg-success-500/20' => $courseStatus === 'passed',
                                            'bg-info-100 dark:bg-info-500/20' => $courseStatus === 'current',
                                            'bg-gray-100 dark:bg-white/10' => $courseStatus === 'remaining',
                                        ])>
                                            @if($courseStatus === 'passed')
                                                <x-heroicon-o-check-circle style="width: 1.1rem; height: 1.1rem;" class="text-success-600 dark:text-success-400" />
                                            @elseif($courseStatus === 'current')
                                                <x-heroicon-o-arrow-path style="width: 1.1rem; height: 1.1rem;" class="text-info-600 dark:text-info-400" />
                                            @else
                                                <x-heroicon-o-book-open style="width: 1.1rem; height: 1.1rem;" class="text-gray-400 dark:text-gray-500" />
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-sm text-gray-900 dark:text-white leading-snug">{{ $course->name }}</p>
                                            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-mono">{{ $course->code }}</span>
                                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                                <span>{{ $course->credits }} وحدة</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($grade && $courseStatus === 'passed')
                                            <span @class([
                                                'inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold',
                                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => ! in_array($grade['letter'], ['D', 'D+']),
                                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => in_array($grade['letter'], ['D', 'D+']),
                                            ])>{{ $grade['letter'] }}</span>
                                        @endif
                                        <span @class([
                                            'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium whitespace-nowrap',
                                            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $courseStatus === 'passed',
                                            'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $courseStatus === 'current',
                                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $courseStatus === 'remaining',
                                        ])>
                                            @if($courseStatus === 'passed') مجتاز
                                            @elseif($courseStatus === 'current') قيد الدراسة
                                            @else لم يُدرس بعد
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Expandable Grade Detail --}}
                                @if($courseStatus === 'passed' && $grade)
                                    <div class="mt-3 pt-3 border-t {{ $courseStatus === 'passed' ? 'border-success-200 dark:border-success-500/20' : 'border-gray-200 dark:border-white/10' }}">
                                        <button wire:click="toggleCourseDetail({{ $course->id }})"
                                            class="flex items-center gap-1.5 text-xs font-medium {{ $courseStatus === 'passed' ? 'text-success-700 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }} hover:underline">
                                            @if($isExpanded)
                                                <x-heroicon-o-chevron-up style="width: 0.875rem; height: 0.875rem;" />
                                                إخفاء التفاصيل
                                            @else
                                                <x-heroicon-o-chevron-down style="width: 0.875rem; height: 0.875rem;" />
                                                عرض الدرجات
                                            @endif
                                        </button>

                                        @if($isExpanded)
                                            <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                                                <div class="rounded-lg bg-white/60 dark:bg-white/5 p-2">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">أعمال الفصل</p>
                                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $grade['coursework_grade'] ?? '—' }}</p>
                                                </div>
                                                <div class="rounded-lg bg-white/60 dark:bg-white/5 p-2">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">النهائي</p>
                                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $grade['final_grade'] ?? '—' }}</p>
                                                </div>
                                                <div class="rounded-lg bg-white/60 dark:bg-white/5 p-2">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">المجموع</p>
                                                    <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $grade['total'] ?? '—' }}</p>
                                                </div>
                                            </div>
                                            @if($semesterName)
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-center">{{ $semesterName }}</p>
                                            @endif
                                        @endif
                                    </div>
                                @elseif($courseStatus === 'current')
                                    <div class="mt-3 pt-3 border-t border-info-200 dark:border-info-500/20">
                                        <p class="text-xs text-info-600 dark:text-info-400 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-info-500 animate-pulse flex-shrink-0"></span>
                                            مسجّل في الفصل الحالي
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endforeach
    @endif
</x-filament-panels::page>
