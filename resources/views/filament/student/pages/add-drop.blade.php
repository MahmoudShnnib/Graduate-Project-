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
        $pendingDropOfferingIds = $this->getPendingDropOfferingIds();
        $pendingAddOfferingIds = $this->getPendingAddOfferingIds();
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();
        $allRequests = $this->getAllRequestsThisSemester();
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- My submissions (persistent history of decisions this semester) --}}
    @if($allRequests->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-check style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>طلباتك لهذا الفصل</span>
                    <span class="text-xs font-normal text-gray-400">({{ $allRequests->count() }})</span>
                </div>
            </x-slot>
            <x-slot name="description">هنا تجد حالة كل طلب قدمته — سواء تم قبوله أو رفضه أو لا يزال قيد المراجعة.</x-slot>

            <div class="space-y-2">
                @foreach($allRequests as $req)
                    @php
                        $isDrop = $req->type === \App\Enums\AddDropType::Drop;
                        $typeLabel = $isDrop ? 'حذف' : 'إضافة';
                        $statusKey = is_string($req->status) ? $req->status : ($req->status?->value ?? 'pending');

                        $statusConfig = match ($statusKey) {
                            'pending' => [
                                'label' => 'قيد المراجعة',
                                'icon' => 'heroicon-o-clock',
                                'border' => 'border-warning-200 dark:border-warning-500/30',
                                'bg' => 'bg-warning-50/40 dark:bg-warning-500/5',
                                'badgeBg' => 'bg-warning-100 dark:bg-warning-500/10',
                                'badgeText' => 'text-warning-700 dark:text-warning-400',
                            ],
                            'approved' => [
                                'label' => 'تم القبول',
                                'icon' => 'heroicon-o-check-circle',
                                'border' => 'border-success-200 dark:border-success-500/30',
                                'bg' => 'bg-success-50/40 dark:bg-success-500/5',
                                'badgeBg' => 'bg-success-100 dark:bg-success-500/10',
                                'badgeText' => 'text-success-700 dark:text-success-400',
                            ],
                            'rejected' => [
                                'label' => 'تم الرفض',
                                'icon' => 'heroicon-o-x-circle',
                                'border' => 'border-danger-200 dark:border-danger-500/30',
                                'bg' => 'bg-danger-50/40 dark:bg-danger-500/5',
                                'badgeBg' => 'bg-danger-100 dark:bg-danger-500/10',
                                'badgeText' => 'text-danger-700 dark:text-danger-400',
                            ],
                            default => [
                                'label' => $statusKey,
                                'icon' => 'heroicon-o-question-mark-circle',
                                'border' => 'border-gray-200 dark:border-white/10',
                                'bg' => 'bg-white dark:bg-gray-900',
                                'badgeBg' => 'bg-gray-100 dark:bg-white/10',
                                'badgeText' => 'text-gray-700 dark:text-gray-300',
                            ],
                        };
                    @endphp

                    <div class="flex items-center justify-between p-4 rounded-xl border {{ $statusConfig['border'] }} {{ $statusConfig['bg'] }}">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $isDrop ? 'bg-danger-100 dark:bg-danger-500/20' : 'bg-primary-100 dark:bg-primary-500/20' }}">
                                @if($isDrop)
                                    <x-heroicon-o-minus-circle style="width: 1rem; height: 1rem;" class="text-danger-600 dark:text-danger-400" />
                                @else
                                    <x-heroicon-o-plus-circle style="width: 1rem; height: 1rem;" class="text-primary-600 dark:text-primary-400" />
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-sm text-gray-900 dark:text-white">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mx-1">[{{ $typeLabel }}]</span>
                                    {{ $req->courseOffering->course->name ?? '—' }}
                                </p>
                                <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">{{ $req->courseOffering->course->code ?? '' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $statusConfig['badgeText'] }} {{ $statusConfig['badgeBg'] }} px-2 py-1 rounded-md">
                            <x-dynamic-component :component="$statusConfig['icon']" style="width: 0.875rem; height: 0.875rem;" />
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

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
        <form wire:submit.prevent="submitRequests" class="space-y-6">
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
                <x-slot name="description">اختر المقررات التي ترغب بحذفها — الحد الأقصى {{ $maxDrops }} مقررات</x-slot>

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
                            @php $isPendingDrop = in_array($enrollment->course_offering_id, $pendingDropOfferingIds, true); @endphp
                            <label class="flex items-center justify-between p-4 rounded-xl border {{ $isPendingDrop ? 'border-warning-300 dark:border-warning-500/40 bg-warning-50/40 dark:bg-warning-500/5' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900' }} {{ $isPendingDrop ? '' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800' }} transition">
                                <div class="flex items-center gap-3">
                                    @if($isPendingDrop)
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-warning-500 text-white">
                                            <x-heroicon-s-check style="width: 0.875rem; height: 0.875rem;" />
                                        </div>
                                    @else
                                        <input
                                            type="checkbox"
                                            wire:model="selectedDropEnrollments.{{ $enrollment->id }}"
                                            @disabled($alreadySubmitted)
                                            class="fi-checkbox-input rounded border-gray-300 text-danger-600 shadow-sm focus:ring-danger-500"
                                        />
                                    @endif
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10">
                                        <x-heroicon-o-book-open style="width: 1rem; height: 1rem;" class="text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $enrollment->courseOffering->course->name }}</p>
                                        <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">{{ $enrollment->courseOffering->course->code }}</p>
                                    </div>
                                </div>
                                @if($isPendingDrop)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-700 dark:text-warning-400 bg-warning-100 dark:bg-warning-500/10 px-2 py-1 rounded-md">
                                        <x-heroicon-o-clock style="width: 0.875rem; height: 0.875rem;" />
                                        طلب حذف قيد المراجعة
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label for="dropReason" class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                            سبب الحذف <span class="text-gray-400 font-normal">(اختياري)</span>
                        </label>
                        <x-filament::input.wrapper>
                            <textarea id="dropReason" wire:model="dropReason" rows="2"
                                class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 sm:leading-6"
                                placeholder="اذكر سبب الحذف إن وجد"></textarea>
                        </x-filament::input.wrapper>
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
                <x-slot name="description">اختر المقررات المتاحة من تخصصك — الحد الأقصى {{ $maxAdds }} مقررات</x-slot>

                @if($available->isEmpty())
                    <div class="text-center py-8">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                            <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                        </div>
                        <p class="text-gray-400 dark:text-gray-500 text-sm">لا توجد مقررات متاحة للإضافة.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($available as $offering)
                            @php $isPendingAdd = in_array($offering->id, $pendingAddOfferingIds, true); @endphp
                            <label class="flex items-center justify-between p-4 rounded-xl border {{ $isPendingAdd ? 'border-warning-300 dark:border-warning-500/40 bg-warning-50/40 dark:bg-warning-500/5' : 'border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900' }} {{ $isPendingAdd ? '' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800' }} transition">
                                <div class="flex items-center gap-3">
                                    @if($isPendingAdd)
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-warning-500 text-white">
                                            <x-heroicon-s-check style="width: 0.875rem; height: 0.875rem;" />
                                        </div>
                                    @else
                                        <input
                                            type="checkbox"
                                            wire:model="selectedAddOfferings.{{ $offering->id }}"
                                            @disabled($alreadySubmitted)
                                            class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                        />
                                    @endif
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10">
                                        <x-heroicon-o-book-open style="width: 1rem; height: 1rem;" class="text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $offering->course->name }}</p>
                                        <p class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $offering->course->code }}
                                            {{ $offering->faculty ? '— ' . $offering->faculty->name : '' }}
                                        </p>
                                    </div>
                                </div>
                                @if($isPendingAdd)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-700 dark:text-warning-400 bg-warning-100 dark:bg-warning-500/10 px-2 py-1 rounded-md">
                                        <x-heroicon-o-clock style="width: 0.875rem; height: 0.875rem;" />
                                        طلب إضافة قيد المراجعة
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label for="addReason" class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                            سبب الإضافة <span class="text-gray-400 font-normal">(اختياري)</span>
                        </label>
                        <x-filament::input.wrapper>
                            <textarea id="addReason" wire:model="addReason" rows="2"
                                class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 sm:leading-6"
                                placeholder="اذكر سبب الإضافة إن وجد"></textarea>
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </x-filament::section>

            {{-- Single submit --}}
            <div class="flex justify-end">
                <x-filament::button
                    type="submit"
                    icon="heroicon-o-paper-airplane"
                    size="lg"
                    :disabled="$alreadySubmitted"
                    wire:loading.attr="disabled"
                >
                    إرسال الطلبات
                </x-filament::button>
            </div>
        </form>
    @endif
</x-filament-panels::page>
