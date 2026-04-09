<x-filament-panels::page>
    @php
        $semester  = $this->getActiveSemester();
        $results   = $this->getSurveyResults();
        $maxDemand = $results->max('demand_count') ?: 1;
    @endphp

    @if(! $semester)
        <x-admin.empty-state icon="heroicon-o-calendar" message="لا يوجد فصل دراسي نشط حالياً." />
    @elseif($results->isEmpty())
        <x-admin.empty-state icon="heroicon-o-chart-bar" :message="'لم يتم تقديم أي استطلاعات للفصل: ' . $semester->name" />
    @else
        <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header --}}
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
                </div>
            </div>

            {{-- Table --}}
            <div class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">المقرر</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">التخصصات</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">المجموع</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">أ1</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">أ2</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">أ3</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">أ4</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400">الطلب</th>
                            <th class="fi-ta-header-cell px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($results as $result)
                            @php $enrollUrl = $this->getEnrollStudentsUrl($result->course_id); @endphp
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-4 py-3">
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $result->course->name ?? '—' }}</span>
                                    <span class="block text-xs font-mono text-gray-400 mt-0.5">{{ $result->course->code ?? '' }}</span>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $result->course->specializations->pluck('name')->join('، ') }}
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-center">
                                    <x-admin.badge color="primary" size="md">{{ $result->demand_count }}</x-admin.badge>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-center">
                                    @if($result->priority_1_count > 0)
                                        <x-admin.badge color="success">{{ $result->priority_1_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-center">
                                    @if($result->priority_2_count > 0)
                                        <x-admin.badge color="info">{{ $result->priority_2_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-center">
                                    @if($result->priority_3_count > 0)
                                        <x-admin.badge color="warning">{{ $result->priority_3_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-center">
                                    @if($result->priority_4_count > 0)
                                        <x-admin.badge color="gray">{{ $result->priority_4_count }}</x-admin.badge>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="fi-ta-cell px-4 py-3">
                                    <div class="h-2 rounded-full bg-primary-500/80" style="width: {{ ($result->demand_count / $maxDemand) * 120 }}px"></div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3">
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
    @endif
</x-filament-panels::page>
