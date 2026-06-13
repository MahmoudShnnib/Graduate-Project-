@php
    use App\Filament\Resources\AuditLogs\AuditLogResource;
@endphp

<div
    wire:poll.30s="refresh"
    x-data="{ open: false }"
    x-init="$watch('open', val => { if (val) $wire.markSeen() })"
    @click.outside="open = false"
    class="relative flex items-center"
>
    {{-- Bell button --}}
    <button
        @click="open = !open"
        type="button"
        class="relative flex size-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
        aria-label="آخر نشاطات الإدارة"
    >
        @svg('heroicon-o-bell', 'size-5')

        <span
            x-show="$wire.badgeCount > 0"
            x-cloak
            x-text="$wire.badgeCount > 9 ? '9+' : $wire.badgeCount"
            class="absolute -end-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-amber-500 text-[0.6rem] font-bold text-white ring-2 ring-white dark:ring-gray-900"
        ></span>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute end-0 top-11 z-50 w-80 rounded-2xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        @click.stop
    >
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 dark:border-white/10">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                @svg('heroicon-o-bell', 'size-4')
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-gray-950 dark:text-white">آخر نشاطات الإدارة</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ count($activities) > 0 ? count($activities).' نشاط جديد' : 'لا جديد' }}
                </p>
            </div>
        </div>

        {{-- Activity rows --}}
        <div class="max-h-96 overflow-y-auto py-2">
            @forelse($activities as $item)
                <a
                    href="{{ $item['url'] }}"
                    wire:navigate
                    @click="open = false"
                    class="group mx-2 mb-1 flex items-center gap-3 rounded-xl border border-gray-100 p-3 transition hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-white/5 {{ $item['color'] }}">
                        @svg($item['icon'], 'size-5')
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $item['title'] }}</p>
                        <p class="mt-0.5 truncate text-xs text-gray-400 dark:text-gray-500">بواسطة {{ $item['user'] }}</p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $item['time'] }}</span>
                        <span class="text-xs font-semibold text-blue-600 opacity-0 transition group-hover:opacity-100 dark:text-blue-400">عرض ←</span>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center gap-2 py-8 text-center">
                    @svg('heroicon-o-check-circle', 'size-8 text-emerald-400')
                    <p class="text-sm text-gray-400 dark:text-gray-500">لا توجد نشاطات جديدة</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <a
            href="{{ AuditLogResource::getUrl('index') }}"
            wire:navigate
            @click="open = false"
            class="flex items-center justify-center gap-1.5 rounded-b-2xl border-t border-gray-100 px-4 py-3 text-xs font-semibold text-blue-600 transition hover:bg-gray-50 dark:border-white/10 dark:text-blue-400 dark:hover:bg-white/5"
        >
            @svg('heroicon-o-arrow-left', 'size-3.5')
            عرض كل سجل التدقيق
        </a>
    </div>
</div>
