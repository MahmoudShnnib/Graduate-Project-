<div style="position:relative;">
    <select
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl border border-slate-300 bg-white py-3 text-slate-900 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-200'
        ]) }}
        style="appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:0.85rem;padding-left:2rem;"
    >
        {{ $slot }}
    </select>
    {{-- Custom chevron on the visual left (RTL). Inline-styled because this Tailwind
         v3 CDN build drops the inset/appearance utilities, leaving the arrow stuck. --}}
    <svg style="position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);width:1rem;height:1rem;pointer-events:none;color:#64748b;"
        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
    </svg>
</div>
