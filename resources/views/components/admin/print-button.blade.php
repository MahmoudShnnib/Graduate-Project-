@props(['onclick' => 'window.print()'])

<button
    onclick="{{ $onclick }}"
    {{ $attributes->class(['inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition']) }}
>
    <x-heroicon-o-printer style="width:1rem;height:1rem" />
    {{ $slot->isEmpty() ? 'طباعة' : $slot }}
</button>
