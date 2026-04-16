<x-filament-panels::page>
    {{-- Standard infolist --}}
    @if($this->hasInfolist())
        {{ $this->infolist }}
    @endif

    {{-- Semester Grade Report --}}
    <x-filament::section heading="سادساً: كشف الدرجات حسب الفصل الدراسي" collapsible>
        @php
            $semesters = $this->getStudentSemesters();
            $grades = $this->getSemesterGrades();
        @endphp

        @if($semesters->isEmpty())
            <p class="text-center text-gray-500 py-4">لا توجد فصول دراسية مسجلة لهذا الطالب.</p>
        @else
            <div class="max-w-md mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">اختر الفصل الدراسي</label>
                <select wire:model.live="selectedSemesterId"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <option value="">-- اختر الفصل --</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->selectedSemesterId)
                @if($grades->isEmpty())
                    <p class="text-center text-gray-500 py-4">لا توجد مواد مسجلة في هذا الفصل.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-right">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 font-medium">المادة</th>
                                    <th class="px-4 py-3 font-medium">رمز المادة</th>
                                    <th class="px-4 py-3 font-medium text-center">الساعات</th>
                                    <th class="px-4 py-3 font-medium text-center">أعمال الفصل</th>
                                    <th class="px-4 py-3 font-medium text-center">النهائي</th>
                                    <th class="px-4 py-3 font-medium text-center">المجموع</th>
                                    <th class="px-4 py-3 font-medium text-center">التقدير</th>
                                    <th class="px-4 py-3 font-medium text-center">النقاط</th>
                                    <th class="px-4 py-3 font-medium text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($grades as $enrollment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">{{ $enrollment->courseOffering->course->name }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $enrollment->courseOffering->course->code }}</td>
                                        <td class="px-4 py-3 text-center">{{ $enrollment->courseOffering->course->credits }}</td>
                                        <td class="px-4 py-3 text-center">{{ $enrollment->grade?->coursework_grade ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center">{{ $enrollment->grade?->final_grade ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center font-semibold">{{ $enrollment->grade?->total ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if($enrollment->grade?->letter)
                                                <span @class([
                                                    'font-bold text-base',
                                                    'text-green-600' => in_array($enrollment->grade->letter, ['A', 'B+', 'B', 'C+', 'C']),
                                                    'text-yellow-600' => in_array($enrollment->grade->letter, ['D+', 'D']),
                                                    'text-red-600' => $enrollment->grade->letter === 'F',
                                                ])>{{ $enrollment->grade->letter }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ $enrollment->grade?->points ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if($enrollment->grade?->status?->value === 'approved')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">معتمد</span>
                                            @elseif($enrollment->grade?->status?->value === 'pending_approval')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">قيد المراجعة</span>
                                            @elseif($enrollment->grade)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">مسودة</span>
                                            @else
                                                <span class="text-gray-400 text-xs">لم تُدخل</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Semester Summary --}}
                    @php
                        $approvedGrades = $grades->filter(fn ($e) => $e->grade?->status?->value === 'approved' && $e->grade?->letter !== 'F');
                        $totalCredits = $grades->sum(fn ($e) => $e->courseOffering->course->credits);
                        $approvedCredits = $approvedGrades->sum(fn ($e) => $e->courseOffering->course->credits);
                    @endphp
                    <div class="mt-4 flex gap-6 text-sm text-gray-600">
                        <span><strong>إجمالي الساعات:</strong> {{ $totalCredits }}</span>
                        <span><strong>الساعات المكتسبة:</strong> {{ $approvedCredits }}</span>
                        <span><strong>عدد المواد:</strong> {{ $grades->count() }}</span>
                    </div>
                @endif
            @endif
        @endif
    </x-filament::section>
</x-filament-panels::page>
