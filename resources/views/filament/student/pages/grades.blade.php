<x-filament-panels::page>
    @php
        $semesters = $this->getSemesters();
        $grades = $this->getGrades();
        $student = auth()->user()->student;
    @endphp

    {{-- Stat Cards --}}
    @if($student)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/20">
                    <x-heroicon-o-star style="width: 1.25rem; height: 1.25rem;" class="text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المعدل التراكمي</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 leading-none">{{ number_format((float) $student->gpa, 2) }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-success-100 dark:bg-success-500/20">
                    <x-heroicon-o-academic-cap style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المقررات المجتازة</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $student->completedCoursesCount() }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 flex items-center gap-4">
                <div @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                    'bg-danger-100 dark:bg-danger-500/20' => $student->warnings_count > 0,
                    'bg-gray-100 dark:bg-white/10' => $student->warnings_count === 0,
                ])>
                    <x-heroicon-o-exclamation-triangle style="width: 1.25rem; height: 1.25rem;" @class([
                        'text-danger-600 dark:text-danger-400' => $student->warnings_count > 0,
                        'text-gray-400 dark:text-gray-500' => $student->warnings_count === 0,
                    ]) />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">التحذيرات</p>
                    <p @class([
                        'text-2xl font-bold leading-none',
                        'text-danger-600 dark:text-danger-400' => $student->warnings_count > 0,
                        'text-gray-900 dark:text-white' => $student->warnings_count === 0,
                    ])>{{ $student->warnings_count }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Grades Table --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-table-cells style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>كشف الدرجات</span>
                </div>
                @if($semesters->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500 dark:text-gray-400 font-normal">الفصل:</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedSemesterId">
                                <option value="">الكل</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>
        </x-slot>

        @if($grades->isEmpty())
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد درجات مسجلة.</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="fi-ta-table w-full table-auto text-sm text-right divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">الفصل</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">المقرر</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">ساعات</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">أعمال</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">نهائي</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">المجموع</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">التقدير</th>
                            <th class="fi-ta-header-cell px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">النقاط</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($grades as $enrollment)
                            <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="fi-ta-cell px-4 py-2.5 text-gray-400 dark:text-gray-500 text-xs font-mono">{{ $enrollment->courseOffering->semester->name }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->courseOffering->course->credits }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->grade->coursework_grade ?? '—' }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $enrollment->grade->final_grade ?? '—' }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center font-semibold text-gray-900 dark:text-white">{{ $enrollment->grade->total ?? '—' }}</td>
                                <td class="fi-ta-cell px-4 py-2.5 text-center">
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
                                <td class="fi-ta-cell px-4 py-2.5 text-center text-gray-600 dark:text-gray-400 font-mono">{{ $enrollment->grade->points ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
