<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $courses = $this->getAvailableCourses();
    @endphp

    @if(! $semester)
        <x-filament::section>
            <p class="text-center text-gray-500 py-4">لا يوجد فصل دراسي نشط حالياً. يُرجى المراجعة لاحقاً.</p>
        </x-filament::section>
    @else
        <x-filament::section heading="استطلاع المقررات — {{ $semester->name }}"
            description="اختر المقررات التي ترغب في دراستها هذا الفصل. يمكنك تعديل اختياراتك في أي وقت قبل إغلاق الاستطلاع.">

            @if($courses->isEmpty())
                <p class="text-center text-gray-500 py-4">لا توجد مقررات متاحة حالياً.</p>
            @else
                <div class="space-y-2">
                    @foreach($courses as $course)
                        @php $selected = isset($this->selections[$course->id]); @endphp
                        <div @class([
                            'flex items-center justify-between p-3 rounded-lg border transition-colors',
                            'border-primary-300 bg-primary-50' => $selected,
                            'border-gray-200 hover:border-gray-300' => ! $selected,
                        ])>
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                    wire:click="toggleCourse({{ $course->id }})"
                                    @checked($selected)
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <div>
                                    <p class="font-medium text-sm">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $course->code }} — {{ $course->credits }} ساعة — {{ $course->type?->label() }}</p>
                                </div>
                            </div>
                            @if($selected)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold">
                                    {{ $this->selections[$course->id] }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <p class="text-sm text-gray-500">تم اختيار {{ count($this->selections) }} مقرر</p>
                    <x-filament::button wire:click="submit" icon="heroicon-o-check">
                        حفظ الاختيارات
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
