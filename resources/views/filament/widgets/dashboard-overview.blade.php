<x-filament-widgets::widget>
    @php
        $data = $this->getDashboardData();
    @endphp

    <div class="space-y-6">

        {{-- ===== KPI cards ===== --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($data['kpis'] as $kpi)
                @php
                    $tone = match ($kpi['tone']) {
                        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
                        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
                        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                        default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400',
                    };
                @endphp
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $tone }}">
                            @svg($kpi['icon'], 'size-6')
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</p>
                            <p class="truncate text-2xl font-extrabold text-gray-950 dark:text-white">{{ $kpi['value'] }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-gray-400 dark:text-gray-500">{{ $kpi['sub'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- ===== Funnel + needs-attention ===== --}}
        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Admissions funnel --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="mb-5 flex items-center justify-between gap-2">
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">مسار القبول — {{ $data['semesterName'] }}</h3>
                    <span class="shrink-0 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                        مرفوض: {{ $data['funnel']['rejected'] }}
                    </span>
                </div>

                <div class="space-y-5">
                    @foreach ($data['funnel']['stages'] as $stage)
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stage['label'] }}</span>
                                <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $stage['count'] }}</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-l from-blue-600 to-sky-400 transition-all duration-500"
                                     style="width: {{ max($stage['width'], 2) }}%"></div>
                            </div>
                            @if (! is_null($stage['conversion']))
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    معدل التحويل من المرحلة السابقة:
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $stage['conversion'] }}%</span>
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Needs-attention hub --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-5 text-base font-bold text-gray-950 dark:text-white">يحتاج إجراء</h3>

                <div class="space-y-2">
                    @foreach ($data['attention'] as $item)
                        @php
                            $needs = $item['count'] > 0;
                            $chip = match ($item['tone']) {
                                'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                                'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                                'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
                                'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                                'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
                                'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                                default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400',
                            };
                        @endphp

                        @if ($needs)
                            <a href="{{ $item['url'] }}" wire:navigate
                               class="group flex items-center gap-3 rounded-xl border border-gray-100 p-3 transition hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $chip }}">
                                    @svg($item['icon'], 'size-5')
                                </span>
                                <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-sm font-bold text-gray-900 dark:bg-white/10 dark:text-white">{{ $item['count'] }}</span>
                                <span class="text-xs font-semibold text-blue-600 opacity-0 transition group-hover:opacity-100 dark:text-blue-400">مراجعة ←</span>
                            </a>
                        @else
                            <div class="flex items-center gap-3 rounded-xl p-3 opacity-60">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                                    @svg($item['icon'], 'size-5')
                                </span>
                                <span class="flex-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                                @svg('heroicon-o-check-circle', 'size-5 text-emerald-500')
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
