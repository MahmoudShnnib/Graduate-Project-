<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">معلومات المادة</x-slot>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">المقرر</p>
                <p class="font-semibold">{{ $this->record->course->name }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">الرمز</p>
                <p class="font-semibold font-mono">{{ $this->record->course->code }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">الفصل الدراسي</p>
                <p class="font-semibold">{{ $this->record->semester->name }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">عضو هيئة التدريس</p>
                <p class="font-semibold">{{ $this->record->faculty?->name ?? 'غير مسند' }}</p>
            </div>
        </div>
    </x-filament::section>

    {{ $this->table }}

</x-filament-panels::page>
