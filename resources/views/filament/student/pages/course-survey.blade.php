<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $courses = $this->getAvailableCourses();
    @endphp

    @if(! $semester)
        <x-filament::section>
            <div class="text-center py-12">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد فصل دراسي نشط حالياً. يُرجى المراجعة لاحقاً.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section
            heading="استطلاع المقررات — {{ $semester->name }}"
            description="اختر المقررات التي ترغب في دراستها هذا الفصل. يمكنك تعديل اختياراتك في أي وقت قبل إغلاق الاستطلاع.">

            @if($courses->isEmpty())
                <div class="text-center py-12">
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
                                    class="rounded border-gray-300 dark:border-white/20 text-primary-600 focus:ring-primary-500 w-4 h-4"
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
