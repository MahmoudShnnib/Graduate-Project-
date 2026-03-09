<button
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center rounded-xl bg-blue-800 px-6 py-3 text-base font-bold text-white shadow hover:bg-blue-900 transition'
    ]) }}
>
    {{ $slot }}
</button>
