@props(['mono' => false, 'muted' => false, 'bold' => false])

<td {{ $attributes->class([
    'px-4 py-3',
    'font-mono text-xs' => $mono,
    'text-gray-400 dark:text-gray-500' => $muted,
    'text-gray-600 dark:text-gray-300' => ! $muted && ! $bold,
    'font-semibold text-gray-900 dark:text-white' => $bold,
]) }}>
    {{ $slot }}
</td>
