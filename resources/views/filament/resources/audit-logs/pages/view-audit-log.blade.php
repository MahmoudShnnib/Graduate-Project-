<x-filament-panels::page>
    @php
        $record      = $this->record;
        $oldValues   = (array) ($record->old_values ?? []);
        $newValues   = (array) ($record->new_values ?? []);
        $actionLabel = $this->getActionLabel();
        $modelLabel  = $this->getModelLabel();
        $roleLabel   = $this->getRoleLabel();
    @endphp

    <div x-data="{ tab: 'info' }">

        {{-- Tab Bar --}}
        <div class="flex gap-1 p-1 rounded-xl bg-gray-100 dark:bg-white/5 w-fit mb-4">
            <button
                @click="tab = 'info'"
                :class="tab === 'info' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-1.5 rounded-lg text-sm font-medium transition">
                معلومات
            </button>
            <button
                @click="tab = 'old'"
                :class="tab === 'old' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-1.5 rounded-lg text-sm font-medium transition">
                القيم القديمة
                @if(! empty($oldValues))
                    <span class="ms-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-200 dark:bg-white/10 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ count($oldValues) }}</span>
                @endif
            </button>
            <button
                @click="tab = 'new'"
                :class="tab === 'new' ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-1.5 rounded-lg text-sm font-medium transition">
                القيم الجديدة
                @if(! empty($newValues))
                    <span class="ms-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-200 dark:bg-white/10 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ count($newValues) }}</span>
                @endif
            </button>
        </div>

        {{-- Tab: Info --}}
        <div x-show="tab === 'info'" x-cloak>
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-information-circle style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>تفاصيل السجل</span>
                    </div>
                </x-slot>

                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المستخدم</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $record->user?->name ?? 'النظام' }}</dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الدور</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $roleLabel }}</dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الإجراء</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $actionLabel }}</dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">النموذج</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $modelLabel }}</dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">معرّف السجل</dt>
                        <dd class="font-medium text-gray-900 dark:text-white font-mono">
                            @php($recordUrl = $this->getRecordUrl())
                            @if($recordUrl)
                                <a href="{{ $recordUrl }}"
                                   class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $record->model_id }}
                                    <x-heroicon-o-arrow-top-right-on-square style="width:.875rem;height:.875rem" />
                                </a>
                            @else
                                {{ $record->model_id ?? '—' }}
                            @endif
                        </dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">عنوان IP</dt>
                        <dd class="font-medium text-gray-900 dark:text-white font-mono" dir="ltr">{{ $record->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5 sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">التاريخ والوقت</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('Y/m/d H:i:s') }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        {{-- Tab: Old Values --}}
        <div x-show="tab === 'old'" x-cloak>
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-uturn-left style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>القيم القديمة</span>
                    </div>
                </x-slot>

                @if(empty($oldValues))
                    <p class="text-sm text-gray-400 dark:text-gray-500 py-2">لا توجد قيم قديمة لهذا السجل.</p>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-1/3">الحقل</th>
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">القيمة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($oldValues as $key => $value)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $this->getFieldLabel($key) }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $this->formatValue($key, $value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Tab: New Values --}}
        <div x-show="tab === 'new'" x-cloak>
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-arrow-path style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>القيم الجديدة</span>
                    </div>
                </x-slot>

                @if(empty($newValues))
                    <p class="text-sm text-gray-400 dark:text-gray-500 py-2">لا توجد قيم جديدة لهذا السجل.</p>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-1/3">الحقل</th>
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">القيمة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($newValues as $key => $value)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $this->getFieldLabel($key) }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $this->formatValue($key, $value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>

    </div>

    {{-- Back link --}}
    <a href="{{ url('/admin/audit-logs') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لسجل التدقيق
    </a>

</x-filament-panels::page>
