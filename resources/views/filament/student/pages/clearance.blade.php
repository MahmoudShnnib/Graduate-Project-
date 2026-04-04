<x-filament-panels::page>
    @php
        $clearance = $this->getClearance();
        $isThesisApproved = $this->isThesisApproved();
        $thesis = auth()->user()->student?->thesisRegistration;
    @endphp

    {{-- Prerequisites Check --}}
    @if(! $isThesisApproved)
        <x-filament::section>
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-yellow-100">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-yellow-600" />
                </div>
                <div>
                    <p class="font-medium text-gray-900">الاستماحة غير متاحة بعد</p>
                    <p class="text-sm text-gray-500 mt-1">يجب أن يتم قبول رسالتك العلمية قبل تقديم طلب الاستماحة.</p>
                    @if($thesis)
                        <p class="text-sm mt-2">
                            حالة رسالتك الحالية:
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ms-1',
                                'bg-yellow-100 text-yellow-800' => $thesis->status === 'pending',
                                'bg-red-100 text-red-800' => $thesis->status === 'rejected',
                            ])>
                                {{ $thesis->status === 'pending' ? 'قيد المراجعة' : 'مرفوضة' }}
                            </span>
                        </p>
                    @else
                        <p class="text-sm text-gray-500 mt-2">لم تقدم طلب تسجيل رسالة بعد.</p>
                    @endif
                </div>
            </div>
        </x-filament::section>

    {{-- Clearance Already Requested --}}
    @elseif($clearance)
        <x-filament::section heading="حالة طلب الاستماحة">
            @php
                $statusColors = ['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red'];
                $statusLabels = ['pending' => 'قيد المراجعة', 'approved' => 'تم اعتمادها', 'rejected' => 'مرفوضة'];
                $color = $statusColors[$clearance->status] ?? 'gray';
                $label = $statusLabels[$clearance->status] ?? $clearance->status;
            @endphp
            <div class="text-center py-8">
                <div @class([
                    'inline-flex h-16 w-16 items-center justify-center rounded-full mb-4',
                    'bg-yellow-100' => $clearance->status === 'pending',
                    'bg-green-100' => $clearance->status === 'approved',
                    'bg-red-100' => $clearance->status === 'rejected',
                ])>
                    @if($clearance->status === 'approved')
                        <x-heroicon-o-check-badge class="w-8 h-8 text-green-600" />
                    @elseif($clearance->status === 'rejected')
                        <x-heroicon-o-x-circle class="w-8 h-8 text-red-600" />
                    @else
                        <x-heroicon-o-clock class="w-8 h-8 text-yellow-600" />
                    @endif
                </div>
                <p class="text-xl font-bold">{{ $label }}</p>
                <p class="text-sm text-gray-500 mt-1">تاريخ الطلب: {{ $clearance->created_at->format('Y/m/d') }}</p>
                @if($clearance->issued_at)
                    <p class="text-sm text-green-600 mt-1">تاريخ الاعتماد: {{ $clearance->issued_at->format('Y/m/d') }}</p>
                @endif
                @if($clearance->form_path)
                    <div class="mt-4">
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
        <x-filament::section heading="طلب الاستماحة">
            <div class="text-center py-6">
                <x-heroicon-o-check-badge class="w-16 h-16 text-primary-400 mx-auto mb-4" />
                <p class="text-gray-700 mb-2">رسالتك مقبولة. يمكنك الآن تقديم طلب الاستماحة.</p>
                <p class="text-sm text-gray-500 mb-6">ستتمكن من تنزيل نموذج الاستماحة بعد مراجعة الطلب والموافقة عليه.</p>
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
