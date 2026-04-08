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
                    <div class="flex items-center gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-900 border border-primary-200 dark:border-primary-500/30 text-primary-700 dark:text-primary-300 font-medium">
                            <x-heroicon-o-calendar-days style="width: 1rem; height: 1rem;" />
                            {{ $semester->name }}
                        </span>
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

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                    'bg-danger-100 dark:bg-danger-500/20' => $student->warnings_count > 0,
                    'bg-gray-100 dark:bg-white/10' => $student->warnings_count === 0,
                ])>
                    <x-heroicon-o-exclamation-triangle style="width: 1.25rem; height: 1.25rem;" @class([
                        'text-danger-600 dark:text-danger-400' => $student->warnings_count > 0,
                        'text-gray-400 dark:text-gray-500' => $student->warnings_count === 0,
                    ]) />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">التحذيرات</p>
                    <p @class([
                        'text-2xl font-bold leading-none',
                        'text-danger-600 dark:text-danger-400' => $student->warnings_count > 0,
                        'text-gray-900 dark:text-white' => $student->warnings_count === 0,
                    ])>{{ $student->warnings_count }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-info-100 dark:bg-info-500/20">
                    <x-heroicon-o-identification style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الحالة</p>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold',
                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $student->status?->value === 'active',
                        'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $student->status?->value === 'suspended',
                        'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $student->status?->value === 'withdrawn',
                        'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $student->status?->value === 'graduated',
                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => ! in_array($student->status?->value, ['active', 'suspended', 'withdrawn', 'graduated']),
                    ])>{{ $student->status?->label() ?? '—' }}</span>
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
    @endif
</x-filament-panels::page>
