<x-filament-panels::page>
    {{-- Standard infolist --}}
    @if($this->hasInfolist())
        {{ $this->infolist }}
    @endif

    {{-- Semester Grade Report --}}
    <x-filament::section heading="سادساً: كشف الدرجات حسب الفصل الدراسي" collapsible>
        @php
            $semesters = $this->getStudentSemesters();
        @endphp

        @if($semesters->isEmpty())
            <p class="text-center text-gray-500 py-4">لا توجد فصول دراسية مسجلة لهذا الطالب.</p>
        @else
            <div class="max-w-md mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اختر الفصل الدراسي</label>
                <select wire:model.live="selectedSemesterId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    <option value="">-- اختر الفصل --</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>

            {{ $this->table }}
        @endif
    </x-filament::section>
</x-filament-panels::page>
