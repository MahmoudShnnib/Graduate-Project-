<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $students = $this->getStudentsWithSurveys();
    @endphp

    @if(! $semester)
        <x-filament::section>
            <p class="text-center text-gray-500">لا يوجد فصل دراسي نشط حالياً.</p>
        </x-filament::section>
    @elseif($students->isEmpty())
        <x-filament::section>
            <p class="text-center text-gray-500">لم يقدم أي طالب استطلاعاً للفصل: {{ $semester->name }}</p>
        </x-filament::section>
    @else
        <x-filament::section :heading="'استطلاعات الطلاب — ' . $semester->name" :description="$students->count() . ' طالب قدموا استطلاعاتهم'">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">رقم القيد</th>
                            <th class="px-4 py-3 font-medium">اسم الطالب</th>
                            <th class="px-4 py-3 font-medium">التخصص</th>
                            <th class="px-4 py-3 font-medium">عدد الاختيارات</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($students as $student)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                    {{ $student->enrollment_no ?? '—' }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $student->user?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $student->specialization?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-500/10 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-400">
                                        {{ $student->course_surveys_count }} / 4
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ $this->getSurveyUrl($student->id) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-primary-50 dark:bg-primary-500/10 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-500/20 transition">
                                        <x-heroicon-o-eye class="size-4" />
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
