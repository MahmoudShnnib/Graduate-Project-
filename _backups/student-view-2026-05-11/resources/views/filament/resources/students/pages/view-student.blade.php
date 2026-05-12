<x-filament-panels::page>
    {{-- Standard infolist --}}
    @if($this->hasInfolist())
        {{ $this->infolist }}
    @endif

    {{-- Enrollment history across all semesters --}}
    <x-filament::section heading="سادساً: المقررات المسجلة" collapsible>
        {{ $this->table }}
    </x-filament::section>

    {{-- Activity Timeline --}}
    @php
        $timeline = $this->getActivityTimeline();
        $timelineLimit = 20;
        $hasMore = $timeline->count() > $timelineLimit;
        $visibleTimeline = $this->showAllActivity ? $timeline : $timeline->take($timelineLimit);
    @endphp
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>سجل النشاط</span>
                @if($timeline->isNotEmpty())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">{{ $timeline->count() }}</span>
                @endif
            </div>
        </x-slot>

        @if($timeline->isEmpty())
            <div class="text-center py-8">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد أنشطة مسجلة لهذا الطالب.</p>
            </div>
        @else
            <div class="relative space-y-0">
                {{-- Vertical line --}}
                <div class="absolute start-5 top-3 bottom-3 w-px bg-gray-200 dark:bg-white/10"></div>

                @foreach($visibleTimeline as $entry)
                    <div class="relative flex items-start gap-4 py-3">
                        {{-- Icon dot --}}
                        <div @class([
                            'relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                            'bg-info-100 dark:bg-info-500/20' => $entry['color'] === 'info',
                            'bg-warning-100 dark:bg-warning-500/20' => $entry['color'] === 'warning',
                            'bg-danger-100 dark:bg-danger-500/20' => $entry['color'] === 'danger',
                            'bg-primary-100 dark:bg-primary-500/20' => $entry['color'] === 'primary',
                            'bg-success-100 dark:bg-success-500/20' => $entry['color'] === 'success',
                            'bg-gray-100 dark:bg-white/10' => $entry['color'] === 'gray',
                        ])>
                            <x-filament::icon
                                :icon="$entry['icon']"
                                @class([
                                    'h-5 w-5',
                                    'text-info-600 dark:text-info-400' => $entry['color'] === 'info',
                                    'text-warning-600 dark:text-warning-400' => $entry['color'] === 'warning',
                                    'text-danger-600 dark:text-danger-400' => $entry['color'] === 'danger',
                                    'text-primary-600 dark:text-primary-400' => $entry['color'] === 'primary',
                                    'text-success-600 dark:text-success-400' => $entry['color'] === 'success',
                                    'text-gray-400 dark:text-gray-500' => $entry['color'] === 'gray',
                                ])
                            />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0 pt-1.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry['title'] }}</p>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $entry['date']->format('Y/m/d — H:i') }}</span>
                                @if($entry['detail'])
                                    <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                                    <span>{{ $entry['detail'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($hasMore)
                <div class="mt-4 text-center">
                    <button
                        wire:click="toggleShowAllActivity"
                        class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline"
                    >
                        {{ $this->showAllActivity ? 'عرض الأحدث فقط' : 'عرض الكل ('.$timeline->count().')' }}
                    </button>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-panels::page>
