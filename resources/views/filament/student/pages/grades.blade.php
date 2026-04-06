<x-filament-panels::page>
    @php
        $semesters = $this->getSemesters();
        $grades = $this->getGrades();
        $student = auth()->user()->student;
    @endphp

    {{-- Stat Cards --}}
    @if($student)
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">المعدل التراكمي</p>
                <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format((float) $student->gpa, 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">المقررات المجتازة</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $student->completedCoursesCount() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">التحذيرات</p>
                <p @class([
                    'text-3xl font-bold',
                    'text-danger-600 dark:text-danger-400' => $student->warnings_count > 0,
                    'text-gray-900 dark:text-white' => $student->warnings_count === 0,
                ])>{{ $student->warnings_count }}</p>
            </div>
        </div>
    @endif

    {{-- Grades Table --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-4">
                <span>كشف الدرجات</span>
                @if($semesters->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400 font-normal">الفصل:</label>
                        <select wire:model.live="selectedSemesterId"
                            class="border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg text-sm py-1 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">الكل</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </x-slot>

        @if($grades->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد درجات مسجلة.</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm text-right">
                    <thead>
                        <tr class="border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">الفصل</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">المقرر</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">ساعات</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">أعمال</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">نهائي</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">المجموع</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">التقدير</th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">النقاط</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($grades as $enrollment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-3 text-gray-400 dark:text-gray-500 text-xs font-mono">{{ $enrollment->courseOffering->semester->name }}</td>
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->grade->coursework_grade ?? '—' }}</td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->grade->final_grade ?? '—' }}</td>
                                <td class="px-6 py-3 text-center font-semibold text-gray-900 dark:text-white">{{ $enrollment->grade->total ?? '—' }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if($enrollment->grade?->letter)
                                        <span @class([
                                            'inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold',
                                            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => ! in_array($enrollment->grade->letter, ['F', 'D']),
                                            'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $enrollment->grade->letter === 'F',
                                            'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $enrollment->grade->letter === 'D',
                                        ])>{{ $enrollment->grade->letter }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400 font-mono">{{ $enrollment->grade->points ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
