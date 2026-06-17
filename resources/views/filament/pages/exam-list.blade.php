<x-filament-panels::page>
    @php
        $specializations = $this->getSpecializations();
        $semesters       = $this->getSemesters();
        $selectedSem     = $this->selectedSemester;
        $applications    = $this->getApplications();
        $activeSpec      = $this->activeSpecialization;
        $activeSpecName  = $activeSpec ? $specializations->firstWhere('id', $activeSpec)?->name : null;
        $selectedCount   = count($this->selected);
        $processed       = $this->getProcessedApplications();
        $isArchived      = $this->isSelectedSemesterArchived();

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

            @php $printParams = array_filter(['specialization' => $activeSpec, 'semester' => $selectedSem]); @endphp
            @if($applications->isNotEmpty() || $processed->isNotEmpty())
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <button type="button" @click="open = ! open"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        <x-heroicon-o-printer style="width:1rem;height:1rem" />
                        <span>طباعة</span>
                        <x-heroicon-m-chevron-down style="width:1rem;height:1rem" />
                    </button>
                    <div x-show="open" x-cloak x-transition
                        class="absolute z-10 mt-1 end-0 w-56 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1">
                        @if($applications->isNotEmpty())
                            <a href="{{ route('print.exam-list', $printParams) }}" target="_blank" rel="noopener" @click="open = false"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-heroicon-o-clipboard-document-list style="width:1rem;height:1rem" />
                                <span>قائمة الامتحان</span>
                            </a>
                        @endif
                        @if($processed->isNotEmpty())
                            <a href="{{ route('print.exam-results', $printParams) }}" target="_blank" rel="noopener" @click="open = false"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-heroicon-o-academic-cap style="width:1rem;height:1rem" />
                                <span>نتائج الامتحان</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Archived semester: read-only history. Only the results table below is shown. --}}
    @if($isArchived)
        <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-4 text-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <x-heroicon-o-archive-box class="h-5 w-5 shrink-0 text-gray-400" />
            <div>
                <p class="font-semibold text-gray-950 dark:text-white">هذا الفصل مؤرشف — للعرض فقط</p>
                <p class="text-gray-500 dark:text-gray-400">لا يمكن قبول أو رفض الطلاب في فصل مؤرشف. نتائج الامتحان التفاضلي لهذا الفصل معروضة بالأسفل كسجل تاريخي.</p>
            </div>
        </div>
    {{-- Content --}}
    @elseif($applications->isEmpty())
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
                                <input type="checkbox" x-data x-on:change="$wire.toggleSelectAll($event.target.checked)"
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
                                    <input type="checkbox" value="{{ $app->id }}" wire:model.live="selected"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600" />
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
                                    <div class="w-24">
                                        <x-filament::input.wrapper>
                                            <input type="number" step="0.01" min="0" max="20"
                                                wire:model="scores.{{ $app->id }}" placeholder="—"
                                                class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-center text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500" />
                                        </x-filament::input.wrapper>
                                    </div>
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

    {{-- Results of already-processed applications (accepted / rejected) --}}
    @if($processed->isNotEmpty())
        <div wire:key="processed-results-{{ $selectedSem }}"
             x-data="{ open: @js($isArchived) }"
             class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6">
            {{-- Header (always visible so the table can be re-expanded) --}}
            <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
                <div class="fi-ta-header flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="fi-ta-header-heading text-base font-semibold text-gray-950 dark:text-white">
                            نتائج الامتحان التفاضلي
                        </h3>
                        <p class="fi-ta-header-description text-sm text-gray-500 dark:text-gray-400">
                            {{ $processed->count() }} طالب — مقبول أو مرفوض
                        </p>
                    </div>
                    <button type="button"
                            x-on:click="open = ! open"
                            x-bind:aria-expanded="open ? 'true' : 'false'"
                            aria-label="إظهار أو إخفاء جدول النتائج"
                            title="إظهار أو إخفاء جدول النتائج"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white transition">
                        <x-heroicon-m-chevron-down
                            class="h-4 w-4 transition-transform duration-200"
                            x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                </div>
            </div>

            {{-- Table body (smoothly collapses via Alpine) --}}
            <div x-show="open" x-collapse.duration.500ms x-cloak class="fi-ta-content overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr>
                            <th class="fi-ta-header-cell w-10">م</th>
                            <th class="fi-ta-header-cell">الاسم الرباعي</th>
                            @if(! $activeSpec)
                                <th class="fi-ta-header-cell">التخصص</th>
                            @endif
                            <th class="fi-ta-header-cell">الرقم الوطني</th>
                            <th class="fi-ta-header-cell text-center">الدرجة</th>
                            <th class="fi-ta-header-cell text-center">النتيجة</th>
                            <th class="fi-ta-header-cell">تاريخ النتيجة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($processed as $i => $app)
                            <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4 text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                                <td class="fi-ta-cell px-3 py-4 font-medium text-gray-950 dark:text-white">{{ $app->name }}</td>
                                @if(! $activeSpec)
                                    <td class="fi-ta-cell px-3 py-4">
                                        <x-admin.badge color="gray">{{ $app->specialization?->name ?? '—' }}</x-admin.badge>
                                    </td>
                                @endif
                                <td class="fi-ta-cell px-3 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $app->national_id ?? '—' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-center font-semibold text-gray-950 dark:text-white">
                                    {{ $app->exam_score !== null ? rtrim(rtrim(number_format((float) $app->exam_score, 2), '0'), '.') : '—' }}
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-center">
                                    @if($app->exam_passed)
                                        <x-admin.badge color="success">مقبول</x-admin.badge>
                                    @else
                                        <x-admin.badge color="danger">مرفوض</x-admin.badge>
                                    @endif
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-gray-500 dark:text-gray-400">{{ $app->exam_result_date?->format('Y/m/d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-filament-panels::page>
