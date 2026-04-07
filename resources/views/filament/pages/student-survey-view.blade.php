<x-filament-panels::page>
    @php
        $entries = $this->getSurveyEntries();
    @endphp

    {{-- Student Info Header --}}
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-6">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">اسم الطالب</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->student->user?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">رقم القيد</p>
                <p class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $this->student->enrollment_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">التخصص</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $this->student->specialization?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">الفصل الدراسي</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $this->semester->name }}</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Survey Entries --}}
    @if($entries->isEmpty())
        <x-filament::section>
            <p class="text-center text-gray-500">لم يقدم هذا الطالب أي اختيارات لهذا الفصل.</p>
        </x-filament::section>
    @else
        <x-filament::section heading="اختيارات المقررات ({{ $entries->count() }})">
            <div class="space-y-3">
                @foreach($entries as $entry)
                    @php
                        $isEnrolled = $entry->enrollment !== null;
                        $hasOffering = $entry->offering !== null;
                    @endphp
                    <div @class([
                        'flex items-center justify-between rounded-xl border p-4',
                        'border-green-300 bg-green-50 dark:border-green-500/30 dark:bg-green-500/10' => $isEnrolled,
                        'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $isEnrolled,
                    ])>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex size-8 items-center justify-center rounded-full bg-gray-700 text-sm font-bold text-white">
                                {{ $entry->survey->priority }}
                            </span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $entry->course?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-mono">{{ $entry->course?->code }}</span>
                                    <span class="mx-1">·</span>
                                    {{ $entry->course?->credits ?? 0 }} ساعة
                                    @if($hasOffering)
                                        <span class="mx-1">·</span>
                                        <span class="text-green-600 dark:text-green-400">المقرر مفتوح</span>
                                    @else
                                        <span class="mx-1">·</span>
                                        <span class="text-gray-400">المقرر غير مفتوح بعد</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isEnrolled)
                                <span class="rounded-full bg-green-100 dark:bg-green-500/20 px-3 py-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                    مسجّل
                                </span>
                                <button
                                    wire:click="unenrollStudent({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من إلغاء تسجيل الطالب في هذا المقرر؟"
                                    class="rounded-lg bg-red-50 dark:bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition"
                                >
                                    إلغاء التسجيل
                                </button>
                            @else
                                <button
                                    wire:click="enrollStudent({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من تسجيل الطالب في هذا المقرر؟"
                                    class="rounded-lg bg-primary-50 dark:bg-primary-500/10 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-500/20 transition"
                                >
                                    تسجيل
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Back link --}}
    <div class="mt-4">
        <a href="{{ url('/admin/student-surveys-list') }}" class="text-sm font-medium text-primary-600 hover:underline">
            &rarr; العودة لقائمة الاستطلاعات
        </a>
    </div>
</x-filament-panels::page>
