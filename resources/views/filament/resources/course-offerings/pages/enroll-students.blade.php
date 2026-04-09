<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <x-admin.info-pair label="المقرر">
                {{ $this->record->course->name }}
                <span class="block text-xs font-mono text-gray-400 font-normal">{{ $this->record->course->code }}</span>
            </x-admin.info-pair>
            <x-admin.info-pair label="الفصل الدراسي">{{ $this->record->semester->name }}</x-admin.info-pair>
            <x-admin.info-pair label="الأستاذ المسند">{{ $this->record->faculty?->name }}</x-admin.info-pair>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">حالة المقرر</p>
                <x-admin.badge :color="$this->record->is_open ? 'success' : 'gray'" size="md">
                    {{ $this->record->is_open ? 'مفتوح للتسجيل' : 'مغلق' }}
                </x-admin.badge>
            </div>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
