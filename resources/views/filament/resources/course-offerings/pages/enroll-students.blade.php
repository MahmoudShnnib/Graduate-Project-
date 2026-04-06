<x-filament-panels::page>
    {{-- Offering summary card --}}
    <x-filament::section>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
                <p class="text-xs font-medium text-gray-500">المقرر</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $this->record->course->name }}</p>
                <p class="text-xs text-gray-400">{{ $this->record->course->code }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">الفصل الدراسي</p>
                <p class="mt-1 font-semibold text-gray-900">{{ $this->record->semester->name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">الأستاذ المسند</p>
                <p class="mt-1 font-semibold text-gray-900">
                    {{ $this->record->faculty?->name ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">حالة المقرر</p>
                <span @class([
                    'mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-green-100 text-green-700' => $this->record->is_open,
                    'bg-gray-100 text-gray-600'   => ! $this->record->is_open,
                ])>
                    {{ $this->record->is_open ? '🔓 مفتوح للتسجيل' : '🔒 مغلق' }}
                </span>
            </div>
        </div>
    </x-filament::section>

    {{-- Students table --}}
    {{ $this->table }}
</x-filament-panels::page>
