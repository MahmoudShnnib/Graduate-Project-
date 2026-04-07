<x-filament-panels::page>
    @php
        $applications = $this->getApplications();
    @endphp

    @if($applications->isEmpty())
        <x-filament::section>
            <div class="text-center py-8">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد طلبات مقبولة مبدئياً حالياً.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section heading="الطلاب المقبولون مبدئياً ({{ $applications->count() }})">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium w-10">
                                <input
                                    type="checkbox"
                                    x-data
                                    x-on:change="
                                        const checkboxes = document.querySelectorAll('.exam-checkbox');
                                        checkboxes.forEach(cb => {
                                            cb.checked = $event.target.checked;
                                            cb.dispatchEvent(new Event('change'));
                                        });
                                    "
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                />
                            </th>
                            <th class="px-4 py-3 font-medium">الاسم الرباعي</th>
                            <th class="px-4 py-3 font-medium">التخصص</th>
                            <th class="px-4 py-3 font-medium">الرقم الوطني</th>
                            <th class="px-4 py-3 font-medium">تاريخ الامتحان</th>
                            <th class="px-4 py-3 font-medium">الدرجة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($applications as $application)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        value="{{ $application->id }}"
                                        wire:model="selected"
                                        class="exam-checkbox rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    />
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $application->name }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $application->specialization?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $application->national_id ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $application->exam_date ? $application->exam_date->format('Y/m/d H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        wire:model="scores.{{ $application->id }}"
                                        placeholder="—"
                                        class="w-20 rounded border-gray-300 dark:border-gray-600 dark:bg-white/5 dark:text-white text-sm text-center focus:border-primary-500 focus:ring-primary-500"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex gap-3 justify-end">
                <x-filament::button
                    wire:click="markFailed"
                    wire:confirm="هل أنت متأكد من رفض الطلاب المحددين؟"
                    color="danger"
                    icon="heroicon-o-x-mark"
                >
                    راسب — رفض
                </x-filament::button>
                <x-filament::button
                    wire:click="markPassed"
                    wire:confirm="هل أنت متأكد من قبول الطلاب المحددين نهائياً؟"
                    color="success"
                    icon="heroicon-o-check"
                >
                    ناجح — قبول نهائي
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
