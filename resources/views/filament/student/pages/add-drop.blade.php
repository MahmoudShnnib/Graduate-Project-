<x-filament-panels::page>
    @php
        $isOpen = $this->isAddDropOpen();
        $enrollments = $this->getEnrollments();
        $available = $this->getAvailableOfferings();
    @endphp

    @if(! $isOpen)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-4">
                    <x-heroicon-o-lock-closed style="width: 1.5rem; height: 1.5rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-700 dark:text-gray-300 font-medium">فترة الإضافة والحذف مغلقة حالياً</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">سيتم فتحها في بداية الفصل الدراسي الجديد</p>
            </div>
        </x-filament::section>
    @else
        {{-- Drop --}}
        <x-filament::section heading="حذف مقرر" description="طلب حذف مقرر مسجّل في الفصل الحالي">
            @if($enrollments->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد تسجيلات حالية.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($enrollments as $enrollment)
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</p>
                                <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">{{ $enrollment->courseOffering->course->code }}</p>
                            </div>
                            <x-filament::button
                                wire:click="requestDrop({{ $enrollment->id }})"
                                wire:confirm="هل أنت متأكد من طلب حذف هذا المقرر؟"
                                color="danger"
                                size="sm"
                                icon="heroicon-o-minus-circle"
                            >
                                طلب حذف
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Add --}}
        <x-filament::section heading="إضافة مقرر" description="طلب إضافة مقرر جديد للفصل الحالي">
            @if($available->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد مقررات متاحة للإضافة.</p>
                </div>
            @else
                <div class="space-y-4 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">اختر المقرر</label>
                        <select wire:model="addOfferingId"
                            class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">— اختر مقرراً —</option>
                            @foreach($available as $offering)
                                <option value="{{ $offering->id }}">
                                    {{ $offering->course->name }} ({{ $offering->course->code }})
                                    {{ $offering->faculty ? '— ' . $offering->faculty->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">السبب <span class="text-gray-400 font-normal">(اختياري)</span></label>
                        <textarea wire:model="addReason" rows="3"
                            class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                            placeholder="اذكر سبب الإضافة إن وجد"></textarea>
                    </div>
                    <x-filament::button wire:click="requestAdd" icon="heroicon-o-plus-circle">
                        إرسال طلب الإضافة
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
