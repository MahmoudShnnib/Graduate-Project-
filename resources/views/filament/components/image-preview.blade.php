@props(['url', 'alt' => ''])

<div class="flex justify-center p-2">
    <img
        src="{{ $url }}"
        alt="{{ $alt }}"
        class="max-h-[75vh] max-w-full rounded-lg shadow-sm"
    />
</div>
