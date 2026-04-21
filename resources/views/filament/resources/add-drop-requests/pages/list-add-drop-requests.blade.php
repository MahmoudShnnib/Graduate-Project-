<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $students = $this->getStudentsWithRequests();
    @endphp

    @if(! $semester)
        <x-admin.empty-state icon="heroicon-o-calendar" message="لا يوجد فصل دراسي نشط حالياً." />
    @elseif($students->isEmpty())
        <x-admin.empty-state icon="heroicon-o-inbox" :message="'لا توجد طلبات إضافة/حذف للفصل: ' . $semester->name" />
    @else
        <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header --}}
            <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
                <div class="fi-ta-header flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="fi-ta-header-heading text-base font-semibold text-gray-950 dark:text-white">
                            طلبات الإضافة/الحذف — {{ $semester->name }}
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
                            <th class="fi-ta-header-cell">الطالب</th>
                            <th class="fi-ta-header-cell">رقم القيد</th>
                            <th class="fi-ta-header-cell">التخصص</th>
                            <th class="fi-ta-header-cell text-center">إجمالي الطلبات</th>
                            <th class="fi-ta-header-cell text-center">قيد المراجعة</th>
                            <th class="fi-ta-header-cell text-center">الحالة</th>
                            <th class="fi-ta-header-cell"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($students as $student)
                            @php
                                $isCompleted = (int) $student->pending_requests_count === 0;
                                $detailUrl = $this->getDetailUrl($student->id);
                            @endphp
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer"
                                onclick="window.location='{{ $detailUrl }}'">
                                <td class="fi-ta-cell px-3 py-4">
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $student->user?->name ?? '—' }}</span>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">
                                    {{ $student->enrollment_no ?? '—' }}
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $student->specialization?->name ?? '—' }}
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    <x-admin.badge color="gray">{{ $student->total_requests_count }}</x-admin.badge>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($student->pending_requests_count > 0)
                                        <x-admin.badge color="warning">{{ $student->pending_requests_count }}</x-admin.badge>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($isCompleted)
                                        <x-admin.badge color="success">مكتمل</x-admin.badge>
                                    @else
                                        <x-admin.badge color="warning">قيد المراجعة</x-admin.badge>
                                    @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <a href="{{ $detailUrl }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline whitespace-nowrap">
                                        <x-heroicon-o-eye style="width:.875rem;height:.875rem" />
                                        عرض الطلبات
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
