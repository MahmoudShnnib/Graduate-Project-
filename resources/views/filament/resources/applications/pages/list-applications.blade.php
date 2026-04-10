<x-filament-panels::page>
    @php
        $specializations = $this->getSpecializations();
        $activeSpec = $this->activeSpecialization;

        $tabs = collect([['id' => null, 'label' => 'الكل', 'count' => \App\Models\Application::count()]]);
        foreach ($specializations as $spec) {
            $tabs->push([
                'id'    => $spec->id,
                'label' => $spec->name,
                'count' => \App\Models\Application::where('specialization_id', $spec->id)->count(),
            ]);
        }
    @endphp

    <div class="flex items-center justify-start">
        <x-admin.filter-tabs :tabs="$tabs" :active="$activeSpec" wire-method="filterBySpecialization" />
    </div>

    {{ $this->content }}
</x-filament-panels::page>
