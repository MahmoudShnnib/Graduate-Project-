<x-filament-panels::page>
    @php
        $auditData      = $this->getAuditLogs();
        $logs           = $auditData['items'];
        $total          = $auditData['total'];
        $from           = $auditData['from'];
        $to             = $auditData['to'];
        $lastPage       = $auditData['lastPage'];
        $activeCount    = $this->activeFilterCount();

        $actionOptions = \App\Models\AuditLog::query()
            ->distinct()
            ->pluck('action', 'action')
            ->mapWithKeys(fn ($v, $k) => [$k => \App\Services\AuditService::actionLabel($k)])
            ->sortBy(fn ($v) => $v)
            ->toArray();

        $userOptions = \App\Models\User::orderBy('name')->get(['id', 'name']);

        $modelTypeOptions = collect(\App\Filament\Resources\AuditLogs\AuditLogResource::MODEL_LABELS)
            ->filter(fn ($label, $class) => \App\Models\AuditLog::where('model_type', $class)->exists())
            ->toArray();

        $roleOptions = \App\Filament\Resources\AuditLogs\AuditLogResource::ROLE_LABELS;

    @endphp

    {{-- ═══════════════════════════════════════════════════ --}}
    {{--  MAIN CARD                                          --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div
        x-data="{ showFilters: false }"
        class="rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
    >

        {{-- ── Header ── --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">سجل التدقيق</h2>

            <button
                type="button"
                @click="showFilters = !showFilters"
                class="relative p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                title="الفلاتر"
            >
                {{-- Filter count badge --}}
                <span
                    class="absolute -end-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full text-xs font-semibold
                           {{ $activeCount > 0
                               ? 'bg-amber-500 text-white'
                               : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' }}"
                >{{ $activeCount }}</span>
                <x-heroicon-o-funnel class="h-5 w-5" />
            </button>
        </div>

        {{-- ── Filter Panel (collapsible) ── --}}
        <div x-show="showFilters" x-collapse class="border-b border-gray-200 dark:border-white/10">
            <div class="p-4">

                {{-- Row 1: search + role + model + dates --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- Search --}}
                    <div class="lg:col-span-2">
                        <label for="al-search" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">بحث باسم المستخدم</label>
                        <input
                            id="al-search"
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="اسم المستخدم..."
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
                        />
                    </div>

                    {{-- Role --}}
                    <div>
                        <label for="al-role" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">الدور</label>
                        <select
                            id="al-role"
                            wire:model.live="filterRole"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                            <option value="">الكل</option>
                            @foreach($roleOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User --}}
                    <div>
                        <label for="al-user" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">المستخدم</label>
                        <select
                            id="al-user"
                            wire:model.live="filterUserId"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                            <option value="">الكل</option>
                            @foreach($userOptions as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Model type --}}
                    <div>
                        <label for="al-model" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">النموذج</label>
                        <select
                            id="al-model"
                            wire:model.live="filterModelType"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                            <option value="">الكل</option>
                            @foreach($modelTypeOptions as $class => $label)
                                <option value="{{ $class }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date from --}}
                    <div>
                        <label for="al-from" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">من تاريخ</label>
                        <input
                            id="al-from"
                            type="date"
                            wire:model.live="filterFrom"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>

                    {{-- Date to --}}
                    <div>
                        <label for="al-to" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">إلى تاريخ</label>
                        <input
                            id="al-to"
                            type="date"
                            wire:model.live="filterTo"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900
                                   focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                                   dark:border-white/10 dark:bg-white/5 dark:text-white"
                        />
                    </div>

                    {{-- Show system + Reset --}}
                    <div class="flex items-end justify-between gap-4 sm:col-span-2 lg:col-span-2">
                        <label for="al-show-system" class="flex cursor-pointer items-center gap-2 pt-5">
                            <input
                                id="al-show-system"
                                type="checkbox"
                                wire:model.live="filterShowSystem"
                                class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">إظهار إجراءات النظام</span>
                        </label>

                        @if($activeCount > 0)
                            <button
                                type="button"
                                wire:click="resetFilters"
                                class="pt-5 text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                            >إعادة تعيين</button>
                        @endif
                    </div>

                </div>

                {{-- Row 2: Action chips --}}
                @if(! empty($actionOptions))
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">الإجراءات</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($actionOptions as $value => $label)
                                @php $isActive = in_array($value, $this->filterActions, true); @endphp
                                <button
                                    type="button"
                                    wire:click="toggleAction('{{ $value }}')"
                                    class="rounded-full px-3 py-1 text-xs font-medium transition
                                           {{ $isActive
                                               ? 'bg-amber-500 text-white'
                                               : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10' }}"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                        <th class="px-6 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">المستخدم</th>
                        <th class="px-6 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">الإجراء</th>
                        <th class="px-6 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">التاريخ</th>
                        <th class="px-6 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">القيم القديمة</th>
                        <th class="px-6 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">القيم الجديدة</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $badgeClass = match(true) {
                                str_contains($log->action, 'grade') || str_contains($log->action, 'Grade')
                                    => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
                                str_contains($log->action, 'application')
                                    => 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
                                str_contains($log->action, 'status') || str_contains($log->action, 'suspension')
                                    => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400',
                                str_contains($log->action, 'discussion') || str_contains($log->action, 'thesis')
                                    => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
                                str_contains($log->action, 'enrolled') || str_contains($log->action, 'dropped') || str_contains($log->action, 'survey')
                                    => 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="border-b border-gray-100 transition last:border-0 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5">

                            {{-- User --}}
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $log->user?->name ?? 'النظام' }}
                                </div>
                                @if($log->user)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \App\Filament\Resources\AuditLogs\AuditLogResource::ROLE_LABELS[(string) $log->user->role] ?? '' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Action badge --}}
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ \App\Services\AuditService::actionLabel($log->action) }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                            </td>

                            {{-- Old values --}}
                            <td class="px-6 py-4">
                                @if(! empty($log->old_values))
                                    @foreach($log->old_values as $key => $value)
                                        <div class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ \App\Filament\Resources\AuditLogs\AuditLogResource::FIELD_LABELS[$key] ?? $key }}:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ \App\Filament\Resources\AuditLogs\AuditLogResource::formatFieldValue($key, $value) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>

                            {{-- New values --}}
                            <td class="px-6 py-4">
                                @if(! empty($log->new_values))
                                    @foreach($log->new_values as $key => $value)
                                        <div class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ \App\Filament\Resources\AuditLogs\AuditLogResource::FIELD_LABELS[$key] ?? $key }}:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ \App\Filament\Resources\AuditLogs\AuditLogResource::formatFieldValue($key, $value) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <a
                                        href="{{ \App\Filament\Resources\AuditLogs\AuditLogResource::getUrl('view', ['record' => $log->getKey()]) }}"
                                        class="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        <x-heroicon-o-eye class="h-4 w-4" />
                                        عرض
                                    </a>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                لا توجد سجلات مطابقة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Footer ── --}}
        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-white/10">

            {{-- Record count --}}
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if($total > 0)
                    عرض {{ $from }} إلى {{ $to }} من أصل {{ $total }} سجل
                @else
                    لا توجد سجلات
                @endif
            </p>

            <div class="flex items-center gap-4">

                {{-- Prev / Next --}}
                @if($lastPage > 1)
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="goToNextPage({{ $lastPage }})"
                            @disabled($this->page >= $lastPage)
                            aria-label="الصفحة التالية"
                            class="rounded p-1 text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <x-heroicon-o-chevron-left class="h-4 w-4" />
                        </button>
                        <span class="min-w-[3rem] text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ $this->page }} / {{ $lastPage }}
                        </span>
                        <button
                            type="button"
                            wire:click="goToPreviousPage"
                            @disabled($this->page <= 1)
                            aria-label="الصفحة السابقة"
                            class="rounded p-1 text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <x-heroicon-o-chevron-right class="h-4 w-4" />
                        </button>
                    </div>
                @endif

                {{-- Per page --}}
                <div class="flex items-center gap-2">
                    <label for="audit-log-per-page" class="text-sm text-gray-600 dark:text-gray-400">في الصفحة</label>
                    <select
                        id="audit-log-per-page"
                        wire:model.live="perPage"
                        class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900
                               focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500
                               dark:border-white/10 dark:bg-white/5 dark:text-white"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

            </div>
        </div>

    </div>{{-- /MAIN CARD --}}



</x-filament-panels::page>
