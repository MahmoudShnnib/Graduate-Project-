<x-filament-panels::page>
    @php
        $semesters = $this->getSemesters();
        $grades = $this->getGrades();
        $student = auth()->user()->student;
    @endphp

    {{-- GPA Summary --}}
    @if($student)
        <x-filament::section>
            <div class="flex items-center gap-8">
                <div class="text-center">
                    <p class="text-4xl font-bold text-primary-600">{{ number_format((float) $student->gpa, 2) }}</p>
                    <p class="text-sm text-gray-500 mt-1">المعدل التراكمي</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold text-gray-700">{{ $student->completedCoursesCount() }}</p>
                    <p class="text-sm text-gray-500 mt-1">المقررات المجتازة</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold text-gray-700">{{ $student->warnings_count }}</p>
                    <p class="text-sm text-gray-500 mt-1">التحذيرات</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Semester Filter --}}
    @if($semesters->isNotEmpty())
        <x-filament::section>
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">تصفية بالفصل:</label>
                <select wire:model.live="selectedSemesterId"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <option value="">جميع الفصول</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filament::section>
    @endif

    {{-- Grades Table --}}
    <x-filament::section heading="كشف الدرجات">
        @if($grades->isEmpty())
            <p class="text-center text-gray-500 py-4">لا توجد درجات مسجلة.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">الفصل</th>
                            <th class="px-4 py-3 font-medium">المقرر</th>
                            <th class="px-4 py-3 font-medium">الساعات</th>
                            <th class="px-4 py-3 font-medium text-center">أعمال الفصل</th>
                            <th class="px-4 py-3 font-medium text-center">الاختبار النهائي</th>
                            <th class="px-4 py-3 font-medium text-center">المجموع</th>
                            <th class="px-4 py-3 font-medium text-center">التقدير</th>
                            <th class="px-4 py-3 font-medium text-center">النقاط</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($grades as $enrollment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $enrollment->courseOffering->semester->name }}</td>
                                <td class="px-4 py-3 font-medium">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="px-4 py-3 text-center">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="px-4 py-3 text-center">{{ $enrollment->grade->coursework_grade ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ $enrollment->grade->final_grade ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $enrollment->grade->total ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($enrollment->grade->letter)
                                        <span @class([
                                            'font-bold text-base',
                                            'text-green-600' => !in_array($enrollment->grade->letter, ['F', 'D']),
                                            'text-red-600' => $enrollment->grade->letter === 'F',
                                            'text-yellow-600' => $enrollment->grade->letter === 'D',
                                        ])>{{ $enrollment->grade->letter }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $enrollment->grade->points ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
