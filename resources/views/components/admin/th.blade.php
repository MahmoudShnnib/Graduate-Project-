@props(['class' => ''])

<th {{ $attributes->class(['px-4 py-3 text-gray-500 dark:text-gray-400 font-semibold text-sm', $class]) }}>
    {{ $slot }}
</th>
