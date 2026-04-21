@props(['url', 'alt' => ''])

<div class="flex justify-center p-2">
    <img
        src="{{ $url }}"
        alt="{{ $alt }}"
        class="max-w-full max-h-[75vh] rounded-lg shadow-sm"
    />
</div>
