<x-filament-panels::page>
    @php
        $isOpen = $this->isAddDropOpen();
        $enrollments = $this->getEnrollments();
        $available = $this->getAvailableOfferings();
    @endphp

    @if(! $isOpen)
        <x-filament::section>
            <div class="text-center py-6">
                <x-heroicon-o-lock-closed style="width: 3rem; height: 3rem; display: block; margin: 0 auto 0.75rem;" class="text-gray-400" />
                <p class="text-gray-600 font-medium">فترة الإضافة والحذف مغلقة حالياً</p>
                <p class="text-sm text-gray-500 mt-1">سيتم فتحها في بداية الفصل الدراسي الجديد</p>
            </div>
        </x-filament::section>
    @else
        {{-- Current Enrollments (Drop) --}}
        <x-filament::section heading="حذف مقرر">
            @if($enrollments->isEmpty())
                <p class="text-center text-gray-500 py-2">لا توجد تسجيلات حالية.</p>
            @else
                <div class="space-y-2">
                    @foreach($enrollments as $enrollment)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200">
                            <div>
                                <p class="font-medium text-sm">{{ $enrollment->courseOffering->course->name }}</p>
                                <p class="text-xs text-gray-500">{{ $enrollment->courseOffering->course->code }}</p>
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

        {{-- Add Course --}}
        <x-filament::section heading="إضافة مقرر">
            @if($available->isEmpty())
                <p class="text-center text-gray-500 py-2">لا توجد مقررات متاحة للإضافة.</p>
            @else
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اختر المقرر</label>
                        <select wire:model="addOfferingId"
                            class="w-full max-w-md border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">-- اختر مقرراً --</option>
                            @foreach($available as $offering)
                                <option value="{{ $offering->id }}">
                                    {{ $offering->course->name }} ({{ $offering->course->code }})
                                    {{ $offering->faculty ? '— ' . $offering->faculty->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">السبب (اختياري)</label>
                        <textarea wire:model="addReason" rows="2"
                            class="w-full max-w-md border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
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
