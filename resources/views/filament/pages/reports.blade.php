<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="اختيار الطالب">
            <div class="max-w-md space-y-2">
                <div>
                    <label for="reports-student-search" class="block text-sm font-medium text-gray-700 mb-1">بحث عن طالب</label>
                    <input
                        type="text"
                        id="reports-student-search"
                        wire:model.live.debounce.300ms="studentSearch"
                        placeholder="رقم القيد أو الاسم"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                    >
                </div>

                <div>
                    <label for="reports-student-picker" class="block text-sm font-medium text-gray-700 mb-1">الطالب</label>
                    <select
                        id="reports-student-picker"
                        wire:model="selectedStudentId"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">-- اختر الطالب --</option>
                        @foreach($this->students as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">يتم عرض أول 50 نتيجة؛ استخدم البحث لتضييق القائمة.</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="التقارير المتاحة">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-filament::button
                    wire:click="downloadTranscript"
                    :disabled="!$selectedStudentId"
                    icon="heroicon-o-document-arrow-down"
                >
                    كشف الدرجات
                </x-filament::button>

                <x-filament::button
                    wire:click="downloadClearanceForm"
                    :disabled="!$selectedStudentId"
                    icon="heroicon-o-document-arrow-down"
                    color="info"
                >
                    نموذج الاستماحة
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
