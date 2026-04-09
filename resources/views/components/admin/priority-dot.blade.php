@props(['value', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'size-6 text-[10px]',
        'md' => 'size-8 text-xs',
    ];
    $colors = match((int) $value) {
        1 => 'bg-green-600 dark:bg-green-500',
        2 => 'bg-blue-600 dark:bg-blue-500',
        3 => 'bg-yellow-600 dark:bg-yellow-500',
        default => 'bg-gray-600 dark:bg-gray-500',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center justify-center rounded-full text-white font-bold', $colors, $sizes[$size] ?? $sizes['md']]) }}>
    {{ $value }}
</span>
