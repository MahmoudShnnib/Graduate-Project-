<x-filament-panels::page>
    @php
        $offerings = $this->getFacultyOfferings();
        $enrollments = $this->getEnrollments();
    @endphp

    {{-- Course Selector --}}
    <x-filament::section heading="اختيار المقرر">
        <div class="max-w-md">
            <select wire:model.live="selectedOfferingId"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="">-- اختر المقرر --</option>
                @foreach($offerings as $offering)
                    <option value="{{ $offering->id }}">
                        {{ $offering->course->name }} — {{ $offering->semester->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-filament::section>

    {{-- Grade Distribution Settings --}}
    @if($this->selectedOfferingId)
        <x-filament::section heading="توزيع الدرجات" collapsible collapsed>
            <p class="text-xs text-gray-500 mb-4">حدد كيفية توزيع الدرجة من 100 بين أعمال الفصل والاختبار النهائي.</p>
            <div class="flex items-end gap-4 flex-wrap">
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">أعمال الفصل</label>
                    <input
                        wire:model="courseworkMax"
                        type="number"
                        min="1"
                        max="99"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الاختبار النهائي</label>
                    <input
                        wire:model="finalMax"
                        type="number"
                        min="1"
                        max="99"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>
                <div class="flex items-center gap-3">
                    <span @class([
                        'text-sm font-bold px-3 py-2 rounded-lg',
                        'bg-green-100 text-green-700' => ($this->courseworkMax + $this->finalMax) === 100,
                        'bg-red-100 text-red-700' => ($this->courseworkMax + $this->finalMax) !== 100,
                    ])>
                        المجموع: {{ $this->courseworkMax + $this->finalMax }}
                    </span>
                    <x-filament::button
                        wire:click="saveDistribution"
                        size="sm"
                        :disabled="($this->courseworkMax + $this->finalMax) !== 100"
                    >
                        حفظ التوزيع
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Grade Entry Table --}}
        @if($enrollments->isEmpty())
            <x-filament::section>
                <p class="text-center text-gray-500 py-4">لا يوجد طلاب مسجلون في هذا المقرر.</p>
            </x-filament::section>
        @else
            <x-filament::section heading="إدخال درجات الطلاب">
                <div class="text-xs text-gray-500 mb-4">
                    أعمال الفصل: من 0 إلى {{ $this->courseworkMax }} — الاختبار النهائي: من 0 إلى {{ $this->finalMax }} — المجموع الكلي: 100
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">الطالب</th>
                                <th class="px-4 py-3 font-medium">رقم القيد</th>
                                <th class="px-4 py-3 font-medium w-36">أعمال الفصل (/{{ $this->courseworkMax }})</th>
                                <th class="px-4 py-3 font-medium w-36">الاختبار النهائي (/{{ $this->finalMax }})</th>
                                <th class="px-4 py-3 font-medium text-center">المجموع</th>
                                <th class="px-4 py-3 font-medium text-center">التقدير</th>
                                <th class="px-4 py-3 font-medium text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($enrollments as $enrollment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium">{{ $enrollment->student->user->name }}</td>
                                    <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $enrollment->student->enrollment_no }}</td>
                                    <td class="px-4 py-2">
                                        @if($enrollment->grade?->status === 'approved')
                                            <span class="text-gray-700">{{ $enrollment->grade->coursework_grade }}</span>
                                        @else
                                            <input
                                                wire:model="gradeInputs.{{ $enrollment->id }}.coursework"
                                                type="number"
                                                min="0"
                                                max="{{ $this->courseworkMax }}"
                                                step="0.5"
                                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                                placeholder="0–{{ $this->courseworkMax }}"
                                            />
                                            @error("gradeInputs.{$enrollment->id}.coursework")
                                                <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($enrollment->grade?->status === 'approved')
                                            <span class="text-gray-700">{{ $enrollment->grade->final_grade }}</span>
                                        @else
                                            <input
                                                wire:model="gradeInputs.{{ $enrollment->id }}.final"
                                                type="number"
                                                min="0"
                                                max="{{ $this->finalMax }}"
                                                step="0.5"
                                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                                                placeholder="0–{{ $this->finalMax }}"
                                            />
                                            @error("gradeInputs.{$enrollment->id}.final")
                                                <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold">
                                        {{ $enrollment->grade?->total ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($enrollment->grade?->letter)
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
                                    <td class="px-4 py-3 text-center">
                                        @if($enrollment->grade?->status === 'approved')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                معتمد
                                            </span>
                                        @elseif($enrollment->grade?->status === 'pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                                قيد المراجعة
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-filament::button wire:click="saveGrades" icon="heroicon-o-check">
                        حفظ الدرجات
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
