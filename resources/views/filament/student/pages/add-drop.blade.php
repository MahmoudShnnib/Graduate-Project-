<x-filament-panels::page>
    @php
        $isOpen = $this->isAddDropOpen();
        $enrollments = $this->getEnrollments();
        $available = $this->getAvailableOfferings();
        $dropCount = $this->getDropCount();
        $addCount = $this->getAddCount();
        $maxDrops = \App\Filament\Student\Pages\AddDropPage::MAX_DROPS;
        $maxAdds = \App\Filament\Student\Pages\AddDropPage::MAX_ADDS;
        $alreadySubmitted = $this->hasSubmittedThisSemester();
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
        {{-- Already submitted notice --}}
        @if($alreadySubmitted)
            <x-filament::section>
                <div class="flex items-center gap-3 py-2">
                    <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem;" class="text-warning-500 shrink-0" />
                    <p class="text-sm text-gray-700 dark:text-gray-300">لديك طلبات إضافة/حذف قائمة لهذا الفصل. لا يمكنك تقديم طلبات جديدة حتى يتم البت في الطلبات الحالية.</p>
                </div>
            </x-filament::section>
        @endif

        {{-- Drop --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-minus-circle style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>حذف مقرر</span>
                    <span class="text-xs font-normal text-gray-400">({{ $dropCount }} / {{ $maxDrops }})</span>
                </div>
            </x-slot>
            <x-slot name="description">طلب حذف مقرر مسجّل في الفصل الحالي — الحد الأقصى {{ $maxDrops }} مقررات</x-slot>

            @if($enrollments->isEmpty())
                <div class="text-center py-8">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد تسجيلات حالية.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($enrollments as $enrollment)
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10">
                                    <x-heroicon-o-book-open style="width: 1rem; height: 1rem;" class="text-gray-500 dark:text-gray-400" />
                                </div>
                                <div>
                                    <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</p>
                                    <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">{{ $enrollment->courseOffering->course->code }}</p>
                                </div>
                            </div>
                            <x-filament::button
                                wire:click="requestDrop({{ $enrollment->id }})"
                                wire:confirm="هل أنت متأكد من طلب حذف هذا المقرر؟"
                                color="danger"
                                size="sm"
                                icon="heroicon-o-minus-circle"
                                :disabled="$alreadySubmitted || $dropCount >= $maxDrops"
                            >
                                طلب حذف
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Add --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-plus-circle style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>إضافة مقرر</span>
                    <span class="text-xs font-normal text-gray-400">({{ $addCount }} / {{ $maxAdds }})</span>
                </div>
            </x-slot>
            <x-slot name="description">طلب إضافة مقرر من تخصصك — الحد الأقصى {{ $maxAdds }} مقررات</x-slot>

            @if($available->isEmpty())
                <div class="text-center py-8">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد مقررات متاحة للإضافة.</p>
                </div>
            @else
                <div class="space-y-4 max-w-lg">
                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">اختر المقرر</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="addOfferingId">
                                <option value="">— اختر مقرراً —</option>
                                @foreach($available as $offering)
                                    <option value="{{ $offering->id }}">
                                        {{ $offering->course->name }} ({{ $offering->course->code }})
                                        {{ $offering->faculty ? '— ' . $offering->faculty->name : '' }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                            السبب <span class="text-gray-400 font-normal">(اختياري)</span>
                        </label>
                        <x-filament::input.wrapper>
                            <textarea wire:model="addReason" rows="3"
                                class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 sm:leading-6"
                                placeholder="اذكر سبب الإضافة إن وجد"></textarea>
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button wire:click="requestAdd" icon="heroicon-o-plus-circle"
                        :disabled="$alreadySubmitted || $addCount >= $maxAdds">
                        إرسال طلب الإضافة
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
