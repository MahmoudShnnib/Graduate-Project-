<x-filament-panels::page>
    {{-- Standard infolist --}}
    @if($this->hasInfolist())
        {{ $this->infolist }}
    @endif

    {{-- Enrollment history across all semesters --}}
    <x-filament::section heading="سادساً: المقررات المسجلة" collapsible>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
