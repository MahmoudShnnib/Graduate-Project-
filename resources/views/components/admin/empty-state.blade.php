@props(['icon' => 'heroicon-o-inbox', 'message'])

<div class="rounded-xl border border-dashed border-gray-200 dark:border-white/10 py-16 text-center">
    <x-dynamic-component :component="$icon" style="width:2.5rem;height:2.5rem" class="mx-auto text-gray-300 dark:text-gray-600 mb-4" />
    <p class="text-sm text-gray-600 dark:text-gray-500">{{ $message }}</p>
</div>
