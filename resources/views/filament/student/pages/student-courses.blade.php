<x-filament-panels::page>
    @php
        $semester = $this->getActiveSemester();
        $enrollments = $this->getEnrollments();
    @endphp

    @if(! $semester)
        <x-filament::section>
            <p class="text-center text-gray-500 py-4">لا يوجد فصل دراسي نشط حالياً.</p>
        </x-filament::section>
    @elseif($enrollments->isEmpty())
        <x-filament::section heading="مقرراتي — {{ $semester->name }}">
            <p class="text-center text-gray-500 py-4">لم تُسجَّل في أي مقررات هذا الفصل بعد.</p>
        </x-filament::section>
    @else
        <x-filament::section heading="مقرراتي — {{ $semester->name }}">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">المقرر</th>
                            <th class="px-4 py-3 font-medium">الرمز</th>
                            <th class="px-4 py-3 font-medium">الساعات</th>
                            <th class="px-4 py-3 font-medium">عضو هيئة التدريس</th>
                            <th class="px-4 py-3 font-medium">الدرجة</th>
                            <th class="px-4 py-3 font-medium">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($enrollments as $enrollment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="px-4 py-3 text-gray-500 font-mono">{{ $enrollment->courseOffering->course->code }}</td>
                                <td class="px-4 py-3 text-center">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="px-4 py-3">{{ $enrollment->courseOffering->faculty?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($enrollment->grade)
                                        <span class="font-bold">{{ $enrollment->grade->letter }}</span>
                                        <span class="text-gray-500 text-xs ms-1">({{ number_format((float)$enrollment->grade->total, 1) }})</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        'bg-green-100 text-green-700' => $enrollment->status?->value === 'active',
                                        'bg-red-100 text-red-700' => $enrollment->status?->value === 'dropped',
                                        'bg-gray-100 text-gray-700' => ! in_array($enrollment->status?->value, ['active', 'dropped']),
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
