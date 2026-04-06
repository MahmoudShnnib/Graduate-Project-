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
            <div class="py-8 text-center text-gray-500">
                <x-filament::icon icon="heroicon-o-calendar" class="mx-auto mb-3 size-10 text-gray-400" />
                <p>لا يوجد فصل دراسي نشط حالياً. يُرجى المراجعة لاحقاً.</p>
            </div>
        </x-filament::section>

    {{-- Survey closed — show submission status --}}
    @elseif(! $semester->survey_open)
        <x-filament::section
            heading="استطلاع المقررات — {{ $semester->name }}"
            description="الاستطلاع مغلق حالياً. يُرجى انتظار نتائج التسجيل.">

            @if($selectedCount === 0)
                <div class="py-6 text-center text-gray-500">
                    <x-filament::icon icon="heroicon-o-lock-closed" class="mx-auto mb-3 size-10 text-gray-400" />
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
                        <div class="flex items-center justify-between rounded-lg border p-3
                            {{ $status === 'enrolled'     ? 'border-green-300 bg-green-50'  : '' }}
                            {{ $status === 'pending'      ? 'border-yellow-300 bg-yellow-50' : '' }}
                            {{ $status === 'unavailable'  ? 'border-red-200 bg-red-50'      : '' }}
                        ">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-gray-700 text-xs font-bold text-white">
                                    {{ $priority }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $course->code }} — {{ $course->credits }} ساعة</p>
                                </div>
                            </div>
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-semibold',
                                'bg-green-100 text-green-700'  => $status === 'enrolled',
                                'bg-yellow-100 text-yellow-700' => $status === 'pending',
                                'bg-red-100 text-red-600'      => $status === 'unavailable',
                            ])>
                                @if($status === 'enrolled')    ✅ مُسجَّل
                                @elseif($status === 'pending')  ⏳ قيد المراجعة
                                @else                          ❌ المادة غير متاحة هذا الفصل
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

    {{-- Survey open — allow selection --}}
    @else
        <x-filament::section
            heading="استطلاع المقررات — {{ $semester->name }}"
            description="اختر {{ $max }} مقررات بالترتيب الذي تفضله (الأولوية 1 = الأهم). لا يمكن الإرسال إلا بعد اختيار {{ $max }} مقررات.">

            {{-- Progress bar --}}
            <div class="mb-5">
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700">تم اختيار {{ $selectedCount }} من {{ $max }}</span>
                    @if($selectedCount === $max)
                        <span class="font-semibold text-green-600">✓ اكتمل الاختيار</span>
                    @else
                        <span class="text-gray-400">تبقى {{ $max - $selectedCount }}</span>
                    @endif
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                    <div class="h-2 rounded-full bg-primary-500 transition-all duration-300"
                         style="width: {{ ($selectedCount / $max) * 100 }}%"></div>
                </div>

                {{-- Priority slots --}}
                <div class="mt-3 flex gap-2">
                    @for($i = 1; $i <= $max; $i++)
                        @php $courseId = array_search($i, $this->selections); @endphp
                        <div @class([
                            'flex flex-1 items-center gap-2 rounded-lg border px-3 py-2 text-xs transition-all',
                            'border-primary-400 bg-primary-50 text-primary-700' => $courseId !== false,
                            'border-dashed border-gray-300 text-gray-400'        => $courseId === false,
                        ])>
                            <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full
                                {{ $courseId !== false ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}
                                text-xs font-bold">{{ $i }}</span>
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
                <p class="py-4 text-center text-gray-500">لا توجد مقررات متاحة حالياً.</p>
            @else
                <div class="space-y-2">
                    @foreach($courses as $course)
                        @php
                            $selected  = isset($this->selections[$course->id]);
                            $priority  = $selected ? $this->selections[$course->id] : null;
                            $maxed     = ! $selected && $selectedCount >= $max;
                            $pivot     = $course->specializations->first()?->pivot;
                            $typeLabel = match($pivot?->type) {
                                'general'    => 'عام',
                                'specialized'=> 'تخصصي',
                                'elective'   => 'اختياري',
                                default      => '',
                            };
                        @endphp
                        <button
                            wire:click="toggleCourse({{ $course->id }})"
                            type="button"
                            @class([
                                'w-full flex items-center justify-between rounded-lg border px-4 py-3 text-right transition-all',
                                'border-primary-400 bg-primary-50 shadow-sm'          => $selected,
                                'border-gray-200 hover:border-primary-300 bg-white'   => ! $selected && ! $maxed,
                                'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed' => $maxed,
                            ])
                            @if($maxed) disabled @endif
                        >
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'flex size-6 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold',
                                    'border-primary-600 bg-primary-600 text-white' => $selected,
                                    'border-gray-300 bg-white text-transparent'     => ! $selected,
                                ])>
                                    {{ $priority ?? '' }}
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-800">{{ $course->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $course->code }} &mdash; {{ $course->credits }} ساعة
                                        @if($typeLabel) &mdash; {{ $typeLabel }} @endif
                                    </p>
                                </div>
                            </div>
                            @if($selected)
                                <x-filament::icon icon="heroicon-o-x-circle"
                                    class="size-5 shrink-0 text-primary-400 hover:text-red-500" />
                            @else
                                <x-filament::icon icon="heroicon-o-plus-circle"
                                    class="size-5 shrink-0 text-gray-300" />
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between border-t pt-4">
                    <p class="text-sm text-gray-500">
                        @if($selectedCount < $max)
                            اختر {{ $max - $selectedCount }} مقرر{{ $max - $selectedCount > 1 ? 'ات' : '' }} إضافية للمتابعة
                        @else
                            جاهز للإرسال ✓
                        @endif
                    </p>
                    <x-filament::button
                        wire:click="submit"
                        icon="heroicon-o-check"
                        :disabled="$selectedCount !== $max"
                        color="{{ $selectedCount === $max ? 'success' : 'gray' }}"
                    >
                        إرسال الاختيارات
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
