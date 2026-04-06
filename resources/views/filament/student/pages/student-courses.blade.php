<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $enrollments = $this->getEnrollments();
    @endphp

    @if(! $semester)
        <x-filament::section>
            <div class="text-center py-12">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد فصل دراسي نشط حالياً.</p>
            </div>
        </x-filament::section>
    @elseif($enrollments->isEmpty())
        <x-filament::section heading="مقرراتي — {{ $semester->name }}">
            <div class="text-center py-12">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لم تُسجَّل في أي مقررات هذا الفصل بعد.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <span>مقرراتي</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                        {{ $semester->name }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                        {{ $enrollments->count() }} مقررات
                    </span>
                </div>
            </x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm text-right">
                    <thead>
                        <tr class="border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">المقرر</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">الرمز</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الساعات</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">عضو هيئة التدريس</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الدرجة</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($enrollments as $enrollment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->courseOffering->course->code }}</td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->faculty?->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if($enrollment->grade)
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $enrollment->grade->letter }}</span>
                                        <span class="text-gray-400 dark:text-gray-500 text-xs ms-1">({{ number_format((float)$enrollment->grade->total, 1) }})</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $enrollment->status?->value === 'active',
                                        'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $enrollment->status?->value === 'dropped',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => ! in_array($enrollment->status?->value, ['active', 'dropped']),
                                    ])>{{ $enrollment->status?->label() ?? $enrollment->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
