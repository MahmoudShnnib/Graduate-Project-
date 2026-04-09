<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $students = $this->getStudentsWithSurveys();
    @endphp

    @if(! $semester)
        <x-admin.empty-state icon="heroicon-o-calendar" message="لا يوجد فصل دراسي نشط حالياً." />
    @elseif($students->isEmpty())
        <x-admin.empty-state icon="heroicon-o-users" :message="'لم يقدم أي طالب استطلاعاً للفصل: ' . $semester->name" />
    @else
        <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header --}}
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

            {{-- Table --}}
            <div class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">رقم القيد</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">اسم الطالب</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">التخصص</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">الاختيارات</th>
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
                                    <x-admin.badge color="primary">{{ $student->course_surveys_count }} / 4</x-admin.badge>
                                </td>
                                <td class="fi-ta-cell px-4 py-3">
                                    <a href="{{ $this->getSurveyUrl($student->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">
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
</x-filament-panels::page>
