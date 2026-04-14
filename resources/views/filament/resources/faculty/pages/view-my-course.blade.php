<x-filament-panels::page>
    {{-- Course Info --}}
    <x-filament::section>
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <x-admin.info-pair label="المادة">
                {{ $this->record->course->name }}
                <span class="block text-xs font-mono text-gray-400 font-normal">{{ $this->record->course->code }}</span>
            </x-admin.info-pair>

            <x-admin.info-pair label="الفصل الدراسي">
                {{ $this->record->semester->name }}
            </x-admin.info-pair>

            <x-admin.info-pair label="الساعات المعتمدة">
                {{ $this->record->course->credits }} ساعة
            </x-admin.info-pair>

            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ورقة الدرجات</p>
                @if($this->record->paper_sheet_path)
                    <a href="{{ asset('storage/' . $this->record->paper_sheet_path) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-sm text-primary-600 hover:underline">
                        <x-heroicon-o-paper-clip class="w-4 h-4" />
                        عرض الورقة
                    </a>
                @else
                    <span class="text-sm text-gray-400">لم يُرفق بعد</span>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Students Table --}}
    {{ $this->table }}
</x-filament-panels::page>
