@props(['color' => 'gray', 'size' => 'sm'])

@php
    $colors = [
        'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400',
        'success' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
        'danger'  => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
        'info'    => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
        'gray'    => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400',
    ];
    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full font-semibold', $colors[$color] ?? $colors['gray'], $sizes[$size] ?? $sizes['sm']]) }}>
    {{ $slot }}
</span>
