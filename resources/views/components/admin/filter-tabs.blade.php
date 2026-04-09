@props(['tabs', 'active', 'wireMethod' => 'filter'])

{{-- $tabs = [ ['id' => null, 'label' => 'All', 'count' => 10], ... ] --}}

<nav class="flex items-center gap-1 rounded-xl bg-gray-100 dark:bg-white/5 p-1">
    @foreach($tabs as $tab)
        @php $isActive = $active === $tab['id']; @endphp
        <button
            wire:click="{{ $wireMethod }}({{ $tab['id'] ?? 'null' }})"
            @class([
                'rounded-lg px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap',
                'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 shadow-sm' => $isActive,
                'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' => ! $isActive,
            ])
        >
            {{ $tab['label'] }}
            @if(isset($tab['count']))
                <x-admin.badge :color="$isActive ? 'primary' : 'gray'" size="xs" class="ms-1">{{ $tab['count'] }}</x-admin.badge>
            @endif
        </button>
    @endforeach
</nav>
