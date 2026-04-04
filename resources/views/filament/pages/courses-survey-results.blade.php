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
                    <table class="w-full text-right">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 px-4">المقرر</th>
                                <th class="py-2 px-4">عدد الطلاب</th>
                                <th class="py-2 px-4">الطلب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $index => $result)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4 font-medium">{{ $result->course->name ?? '-' }}</td>
                                    <td class="py-2 px-4">{{ $result->demand_count }}</td>
                                    <td class="py-2 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 bg-primary-500 rounded" style="width: {{ ($result->demand_count / max($results->max('demand_count'), 1)) * 200 }}px"></div>
                                        </div>
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
