<x-filament-panels::page>
    @php
        $record      = $this->record;
        $oldValues   = (array) ($record->old_values ?? []);
        $newValues   = (array) ($record->new_values ?? []);
        $actionLabel = $this->getActionLabel();
        $actionDesc  = $this->getActionDescription();
        $modelLabel  = $this->getModelLabel();
        $roleLabel   = $this->getRoleLabel();
        $changeKeys  = array_keys($oldValues + $newValues);
        $hasChanges  = ! empty($changeKeys);
    @endphp

    <div class="space-y-4">

        {{-- What happened (brief explanation) --}}
        @if($actionDesc)
            <div class="flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/20 dark:bg-primary-500/10">
                <x-heroicon-o-information-circle class="mt-0.5 shrink-0 text-primary-500" style="width:1.25rem;height:1.25rem;" />
                <div>
                    <p class="text-sm font-bold text-primary-900 dark:text-primary-200">{{ $actionLabel }}</p>
                    <p class="mt-0.5 text-sm text-primary-800/80 dark:text-primary-200/70">{{ $actionDesc }}</p>
                </div>
            </div>
        @endif

        {{-- Everything in one card: details + changes --}}
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
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">التاريخ والوقت</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('Y/m/d H:i:s') }}</dd>
                </div>
            </dl>

            {{-- Changes (old → new) in the same card --}}
            <div class="mt-6 border-t border-gray-100 pt-5 dark:border-white/10">
                <div class="mb-3 flex items-center gap-2">
                    <x-heroicon-o-arrows-right-left style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">التغييرات</span>
                </div>

                @if(! $hasChanges)
                    <p class="py-2 text-sm text-gray-400 dark:text-gray-500">هذا السجل لا يتضمّن تغييرات في القيم (مثل إضافة أو حذف سجل).</p>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 w-1/3">الحقل</th>
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400">القيمة السابقة</th>
                                    <th class="px-4 py-2.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400">القيمة الجديدة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($changeKeys as $key)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $this->getFieldLabel($key) }}</td>
                                        <td class="px-4 py-3 text-gray-500 line-through decoration-gray-300 dark:text-gray-400 dark:decoration-gray-600">
                                            {{ array_key_exists($key, $oldValues) ? $this->formatValue($key, $oldValues[$key]) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                            {{ array_key_exists($key, $newValues) ? $this->formatValue($key, $newValues[$key]) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Other actions from the same operation --}}
        @php($siblings = $this->getBatchSiblings())
        @if($siblings->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-square-3-stack-3d style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>إجراءات أخرى ضمن نفس العملية</span>
                        <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-gray-100 dark:bg-white/10 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $siblings->count() }}</span>
                    </div>
                </x-slot>

                <p class="mb-3 text-xs text-gray-400 dark:text-gray-500">نتجت هذه السجلات جميعها عن نفس الإجراء الواحد.</p>

                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($siblings as $sibling)
                        <a href="{{ $this->siblingUrl($sibling) }}"
                           class="group flex items-center justify-between gap-3 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 rounded-lg">
                            <span class="truncate text-sm text-gray-700 dark:text-gray-200">{{ $this->describeSibling($sibling) }}</span>
                            <span class="shrink-0 text-xs font-medium text-primary-600 opacity-0 transition group-hover:opacity-100 dark:text-primary-400">عرض ←</span>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Back link --}}
        <a href="{{ url('/admin/audit-logs') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
            <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
            العودة لسجل التدقيق
        </a>

    </div>

</x-filament-panels::page>
