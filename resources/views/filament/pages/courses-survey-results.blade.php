<x-filament-panels::page>
    @php
        $activeSemester = $this->getActiveSemester();
        $results = $this->getSurveyResults();
    @endphp

    <div class="space-y-4">
        @if(!$activeSemester)
            <x-filament::section>
                <p class="text-center text-gray-500">لا يوجد فصل دراسي نشط حالياً.</p>
            </x-filament::section>
        @elseif($results->isEmpty())
            <x-filament::section>
                <p class="text-center text-gray-500">لم يتم تقديم أي استطلاعات للفصل الحالي: {{ $activeSemester->name }}</p>
            </x-filament::section>
        @else
            <x-filament::section :heading="'نتائج استطلاع ' . $activeSemester->name">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                            <tr>
                                <th class="py-3 px-4 font-medium">المقرر</th>
                                <th class="py-3 px-4 font-medium">التخصصات</th>
                                <th class="py-3 px-4 font-medium text-center">المجموع</th>
                                <th class="py-3 px-4 font-medium text-center">أولوية 1</th>
                                <th class="py-3 px-4 font-medium text-center">أولوية 2</th>
                                <th class="py-3 px-4 font-medium text-center">أولوية 3</th>
                                <th class="py-3 px-4 font-medium text-center">أولوية 4</th>
                                <th class="py-3 px-4 font-medium">الطلب</th>
                                <th class="py-3 px-4 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($results as $result)
                                @php
                                    $enrollUrl = $this->getEnrollStudentsUrl($result->course_id);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">
                                        {{ $result->course->name ?? '—' }}
                                        <span class="block text-xs font-mono text-gray-400">{{ $result->course->code ?? '' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $result->course->specializations->pluck('name')->join('، ') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-500/10 px-2.5 py-0.5 text-xs font-bold text-primary-700 dark:text-primary-400">
                                            {{ $result->demand_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs">
                                        @if($result->priority_1_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-green-700 font-medium">{{ $result->priority_1_count }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs">
                                        @if($result->priority_2_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-blue-700 font-medium">{{ $result->priority_2_count }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs">
                                        @if($result->priority_3_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-yellow-700 font-medium">{{ $result->priority_3_count }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center text-xs">
                                        @if($result->priority_4_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 font-medium">{{ $result->priority_4_count }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 rounded bg-primary-500" style="width: {{ ($result->demand_count / max($results->max('demand_count'), 1)) * 150 }}px"></div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($enrollUrl)
                                            <a href="{{ $enrollUrl }}"
                                               class="inline-flex items-center gap-1 rounded-lg bg-green-50 dark:bg-green-500/10 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 hover:bg-green-100 transition whitespace-nowrap">
                                                <x-heroicon-o-user-plus class="size-3.5" />
                                                تسجيل الطلاب
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
