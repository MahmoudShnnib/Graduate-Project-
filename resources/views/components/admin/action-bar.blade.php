@props(['selectedCount' => 0, 'emptyLabel' => 'حدد العناصر لتنفيذ إجراء', 'selectedLabel' => 'تم تحديد'])

<div {{ $attributes->class(['flex items-center justify-between gap-4 flex-wrap']) }}>
    <p class="text-sm text-gray-400 dark:text-gray-500">
        @if($selectedCount > 0)
            {{ $selectedLabel }} <span class="font-bold text-gray-900 dark:text-white">{{ $selectedCount }}</span>
        @else
            {{ $emptyLabel }}
        @endif
    </p>
    <div class="flex gap-2">
        {{ $slot }}
    </div>
</div>
