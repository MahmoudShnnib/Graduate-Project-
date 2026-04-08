<x-filament-panels::page>
    @php
        $clearance = $this->getClearance();
        $isThesisApproved = $this->isThesisApproved();
        $thesis = auth()->user()->student?->thesisRegistration;
    @endphp

    {{-- Prerequisites Check --}}
    @if(! $isThesisApproved)
        <x-filament::section>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                    <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <p class="font-semibold text-warning-800 dark:text-warning-300 text-sm">الاستماحة غير متاحة بعد</p>
                    <p class="text-sm text-warning-700 dark:text-warning-400 mt-0.5">يجب أن يتم قبول رسالتك العلمية قبل تقديم طلب الاستماحة.</p>
                    @if($thesis)
                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="text-warning-700 dark:text-warning-400">حالة رسالتك:</span>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $thesis->status === 'pending',
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $thesis->status === 'rejected',
                            ])>{{ $thesis->status === 'pending' ? 'قيد المراجعة' : 'مرفوضة' }}</span>
                        </div>
                    @else
                        <p class="text-sm text-warning-600 dark:text-warning-500 mt-1">لم تقدم طلب تسجيل رسالة بعد.</p>
                    @endif
                </div>
            </div>
        </x-filament::section>

    {{-- Clearance Already Requested --}}
    @elseif($clearance)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-check style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>حالة طلب الاستماحة</span>
                </div>
            </x-slot>

            @php
                $statusConfig = [
                    'pending'  => ['label' => 'قيد المراجعة', 'color' => 'warning', 'icon' => 'clock'],
                    'approved' => ['label' => 'تم اعتمادها',  'color' => 'success', 'icon' => 'check-badge'],
                    'rejected' => ['label' => 'مرفوضة',       'color' => 'danger',  'icon' => 'x-circle'],
                ];
                $config = $statusConfig[$clearance->status] ?? ['label' => $clearance->status, 'color' => 'gray', 'icon' => 'clock'];
            @endphp

            <div class="flex flex-col items-center text-center py-8">
                <div @class([
                    'flex h-16 w-16 items-center justify-center rounded-full mb-4',
                    'bg-warning-100 dark:bg-warning-500/20' => $clearance->status === 'pending',
                    'bg-success-100 dark:bg-success-500/20' => $clearance->status === 'approved',
                    'bg-danger-100 dark:bg-danger-500/20'   => $clearance->status === 'rejected',
                ])>
                    @if($clearance->status === 'approved')
                        <x-heroicon-o-check-badge style="width: 2rem; height: 2rem;" class="text-success-600 dark:text-success-400" />
                    @elseif($clearance->status === 'rejected')
                        <x-heroicon-o-x-circle style="width: 2rem; height: 2rem;" class="text-danger-600 dark:text-danger-400" />
                    @else
                        <x-heroicon-o-clock style="width: 2rem; height: 2rem;" class="text-warning-600 dark:text-warning-400" />
                    @endif
                </div>

                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $config['label'] }}</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">تاريخ الطلب: {{ $clearance->created_at->format('Y/m/d') }}</p>
                @if($clearance->issued_at)
                    <p class="text-sm text-success-600 dark:text-success-400 mt-1">تاريخ الاعتماد: {{ $clearance->issued_at->format('Y/m/d') }}</p>
                @endif
                @if($clearance->form_path)
                    <div class="mt-5">
                        <a href="{{ asset('storage/' . $clearance->form_path) }}" target="_blank">
                            <x-filament::button icon="heroicon-o-document-arrow-down" color="success">
                                تنزيل نموذج الاستماحة
                            </x-filament::button>
                        </a>
                    </div>
                @endif
            </div>
        </x-filament::section>

    {{-- Request Button --}}
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-check style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>طلب الاستماحة</span>
                </div>
            </x-slot>

            <div class="flex flex-col items-center text-center py-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20 mb-4">
                    <x-heroicon-o-check-badge style="width: 2rem; height: 2rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">رسالتك مقبولة</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">يمكنك الآن تقديم طلب الاستماحة.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">ستتمكن من تنزيل نموذج الاستماحة بعد مراجعة الطلب والموافقة عليه.</p>
                <x-filament::button
                    wire:click="requestClearance"
                    wire:confirm="هل أنت متأكد من تقديم طلب الاستماحة؟"
                    icon="heroicon-o-paper-airplane"
                    size="lg"
                >
                    تقديم طلب الاستماحة
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
