@props(['id' => null])

<div @if($id) id="{{ $id }}" @endif {{ $attributes->class(['rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden']) }}>
    <table class="w-full text-sm">
        <thead class="bg-gray-50/80 dark:bg-white/[0.03] text-right">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-gray-900">
            {{ $slot }}
        </tbody>
    </table>
</div>
