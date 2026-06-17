<x-filament-panels::page>
    {{-- Course Info --}}
    <x-filament::section>
        <div class="grid grid-cols-3 gap-6">
            <x-admin.info-pair label="المادة">
                {{ $this->record->course->name }}
                <span class="block text-xs font-mono text-gray-400 font-normal">{{ $this->record->course->code }}</span>
            </x-admin.info-pair>

            <x-admin.info-pair label="الفصل الدراسي">
                {{ $this->record->semester->name }}
            </x-admin.info-pair>

            <x-admin.info-pair label="الوحدات">
                {{ $this->record->course->credits }} وحدة
            </x-admin.info-pair>
        </div>
    </x-filament::section>

    {{-- Students Table --}}
    {{ $this->table }}
</x-filament-panels::page>
