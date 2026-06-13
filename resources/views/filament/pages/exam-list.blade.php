<x-filament-panels::page>
    @php
        $specializations = $this->getSpecializations();
        $semesters       = $this->getSemesters();
        $selectedSem     = $this->selectedSemester;
        $applications    = $this->getApplications();
        $activeSpec      = $this->activeSpecialization;
        $activeSpecName  = $activeSpec ? $specializations->firstWhere('id', $activeSpec)?->name : null;
        $selectedCount   = count($this->selected);

        $tabs = collect([['id' => null, 'label' => 'الكل', 'count' => \App\Models\Application::where('status', 'initially_approved')->when($selectedSem, fn ($q) => $q->where('semester_id', $selectedSem))->count()]]);
        foreach ($specializations as $spec) {
            $tabs->push([
                'id'    => $spec->id,
                'label' => $spec->name,
                'count' => \App\Models\Application::where('status', 'initially_approved')->when($selectedSem, fn ($q) => $q->where('semester_id', $selectedSem))->where('specialization_id', $spec->id)->count(),
            ]);
        }
    @endphp

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <x-admin.filter-tabs :tabs="$tabs" :active="$activeSpec" wire-method="filterBySpecialization" />

        <div class="flex items-center gap-3 flex-wrap">
            <select wire:model.live="selectedSemester"
                class="rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                @endforeach
            </select>

            @if($activeSpec && $applications->isNotEmpty())
                <x-admin.print-button onclick="window.open('{{ route('print.exam-list', $activeSpec) }}', '_blank')" />
            @endif
        </div>
    </div>

    {{-- Content --}}
    @if($applications->isEmpty())
        <x-admin.empty-state
            icon="heroicon-o-clipboard-document-list"
            :message="$activeSpec ? 'لا يوجد طلبات لقسم ' . $activeSpecName : 'لا يوجد طلبات مقبولة مبدئياً'"
        />
    @else
        <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" id="exam-list-table">
            <div class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell w-10">
                                <input type="checkbox" x-data x-on:change="document.querySelectorAll('.exam-cb').forEach(c=>{c.checked=$event.target.checked;c.dispatchEvent(new Event('change'))})"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                            </th>
                            <th class="fi-ta-header-cell w-10">م</th>
                            <th class="fi-ta-header-cell">الاسم الرباعي</th>
                            @if(! $activeSpec)
                                <th class="fi-ta-header-cell">التخصص</th>
                            @endif
                            <th class="fi-ta-header-cell">الرقم الوطني</th>
                            <th class="fi-ta-header-cell">موعد الامتحان</th>
                            <th class="fi-ta-header-cell">الدرجة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($applications as $i => $app)
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4">
                                    <input type="checkbox" value="{{ $app->id }}" wire:model="selected"
                                        class="exam-cb rounded border-gray-300 dark:border-gray-600 text-primary-600" />
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                                <td class="fi-ta-cell px-3 py-4 font-medium text-gray-950 dark:text-white">{{ $app->name }}</td>
                                @if(! $activeSpec)
                                    <td class="fi-ta-cell px-3 py-4">
                                        <x-admin.badge color="gray">{{ $app->specialization?->name ?? '—' }}</x-admin.badge>
                                    </td>
                                @endif
                                <td class="fi-ta-cell px-3 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $app->national_id ?? '—' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-gray-500 dark:text-gray-400">{{ $app->exam_date?->format('Y/m/d H:i') ?? '—' }}</td>
                                <td class="fi-ta-cell px-3 py-4">
                                    <input type="number" step="0.01" min="0" max="100"
                                        wire:model="scores.{{ $app->id }}" placeholder="—"
                                        class="w-20 rounded-lg border-gray-200 dark:border-gray-700 bg-transparent dark:text-white text-sm text-center focus:border-primary-500 focus:ring-primary-500" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Action bar inside the card --}}
            <div class="fi-ta-footer border-t border-gray-200 dark:border-white/10 px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if($selectedCount > 0)
                        تم تحديد <span class="font-bold text-gray-950 dark:text-white">{{ $selectedCount }}</span> طالب
                    @else
                        حدد الطلاب لتنفيذ إجراء
                    @endif
                </p>
                <div class="flex gap-2">
                    {{ $this->markFailedAction }}
                    {{ $this->markPassedAction }}
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
