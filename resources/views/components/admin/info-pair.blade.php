@props(['label', 'mono' => false])

<div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ $label }}</p>
    <p @class(['text-sm text-gray-900 dark:text-white', 'font-mono' => $mono, 'font-semibold' => ! $mono])>
        {{ $slot->isEmpty() ? '—' : $slot }}
    </p>
</div>
