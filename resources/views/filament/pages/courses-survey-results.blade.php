<x-filament-panels::page>
    @php
        $semester  = $this->getActiveSemester();
        $results   = $this->getSurveyResults();
        $maxDemand = $results->max('demand_count') ?: 1;
        $students  = $this->getStudentsWithSurveys();
    @endphp

    @if(! $semester)
        <x-admin.empty-state icon="heroicon-o-calendar" message="لا يوجد فصل دراسي نشط حالياً." />
    @elseif($results->isEmpty())
        <x-admin.empty-state icon="heroicon-o-chart-bar" :message="'لم يتم تقديم أي استطلاعات للفصل: ' . $semester->name" />
    @else
        <div x-data="{ open: @js($this->showCoursesTable) }"
             class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header (stays visible even when table body is collapsed so admin can re-expand) --}}
            <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
                <div class="fi-ta-header flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="fi-ta-header-heading text-base font-semibold text-gray-950 dark:text-white">
                            نتائج استطلاع {{ $semester->name }}
                        </h3>
                        <p class="fi-ta-header-description text-sm text-gray-500 dark:text-gray-400">
                            {{ $results->count() }} مقرر
                        </p>
                    </div>
                    <button type="button"
                            x-on:click="open = ! open; $wire.toggleCoursesTable()"
                            x-bind:aria-expanded="open ? 'true' : 'false'"
                            x-bind:aria-label="open ? 'إخفاء الجدول' : 'إظهار الجدول'"
                            x-bind:title="open ? 'إخفاء الجدول' : 'إظهار الجدول'"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white transition">
                        <x-heroicon-m-chevron-down
                            class="h-4 w-4 transition-transform duration-200"
                            x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                </div>
            </div>

            {{-- Table body (smoothly collapses via Alpine; server prop keeps the preference across reloads) --}}
            <div x-show="open" x-collapse.duration.500ms x-cloak class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell w-10">
                                <input type="checkbox" x-data
                                    x-on:change="document.querySelectorAll('.course-cb').forEach(c=>{c.checked=$event.target.checked;c.dispatchEvent(new Event('change'))})"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                            </th>
                            <th class="fi-ta-header-cell">المقرر</th>
                            <th class="fi-ta-header-cell">التخصصات</th>
                            <th class="fi-ta-header-cell text-center">المجموع</th>
                            <th class="fi-ta-header-cell text-center">أ1</th>
                            <th class="fi-ta-header-cell text-center">أ2</th>
                            <th class="fi-ta-header-cell text-center">أ3</th>
                            <th class="fi-ta-header-cell text-center">أ4</th>
                            <th class="fi-ta-header-cell">الطلب</th>
                            <th class="fi-ta-header-cell"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($results as $result)
                            @php $enrollUrl = $this->getEnrollStudentsUrl($result->course_id); @endphp
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4">
                                    <input type="checkbox" value="{{ $result->course_id }}" wire:model.live="selectedCourses"
                                        class="course-cb rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                                </td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $result->course->name ?? '—' }}</span>
                                    <span class="block text-xs font-mono text-gray-400 mt-0.5">{{ $result->course->code ?? '' }}</span>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $result->course->specializations->pluck('name')->join('، ') }}
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    <x-admin.badge color="primary" size="md">{{ $result->demand_count }}</x-admin.badge>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($result->priority_1_count > 0)
                                        <x-admin.badge color="success">{{ $result->priority_1_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($result->priority_2_count > 0)
                                        <x-admin.badge color="info">{{ $result->priority_2_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($result->priority_3_count > 0)
                                        <x-admin.badge color="warning">{{ $result->priority_3_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($result->priority_4_count > 0)
                                        <x-admin.badge color="gray">{{ $result->priority_4_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <div class="h-2 rounded-full bg-primary-500/80" style="width: {{ ($result->demand_count / $maxDemand) * 120 }}px"></div>
                                </td>
                                <td class="fi-ta-cell px-3 py-4">
                                    @if($enrollUrl)
                                        <a href="{{ $enrollUrl }}" class="inline-flex items-center gap-1.5 rounded-lg text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline whitespace-nowrap">
                                            <x-heroicon-o-user-plus style="width:.875rem;height:.875rem" />
                                            تسجيل
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Students survey list (merged from the retired /admin/student-surveys-list, T10) --}}
        @if($students->isNotEmpty())
            <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6">
                <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
                    <div class="fi-ta-header flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="fi-ta-header-heading text-base font-semibold text-gray-950 dark:text-white">
                                استطلاعات الطلاب — {{ $semester->name }}
                            </h3>
                            <p class="fi-ta-header-description text-sm text-gray-500 dark:text-gray-400">
                                {{ $students->count() }} طالب
                            </p>
                        </div>
                    </div>
                </div>

                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">رقم القيد</th>
                                <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">اسم الطالب</th>
                                <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">التخصص</th>
                                <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">عدد الاختيارات</th>
                                <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">الحالة</th>
                                <th class="fi-ta-header-cell px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($students as $student)
                                <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                        {{ $student->enrollment_no ?? '—' }}
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 font-medium text-gray-950 dark:text-white">
                                        {{ $student->user?->name ?? '—' }}
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $student->specialization?->name ?? '—' }}
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-center">
                                        <x-admin.badge color="primary">{{ $student->course_surveys_count }}</x-admin.badge>
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-center">
                                        @if($student->processed_surveys_count >= $student->course_surveys_count)
                                            <x-admin.badge color="success">مكتمل</x-admin.badge>
                                        @elseif($student->processed_surveys_count > 0)
                                            <x-admin.badge color="warning">جزئي {{ $student->processed_surveys_count }}/{{ $student->course_surveys_count }}</x-admin.badge>
                                        @else
                                            <x-admin.badge color="gray">معلّق</x-admin.badge>
                                        @endif
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3">
                                        <a href="{{ $this->getStudentSurveyUrl($student->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                                            <x-heroicon-o-eye style="width:.875rem;height:.875rem" />
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
