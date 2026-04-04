<x-filament-panels::page>
    @php
        $history = $this->getHistory();
        $hasPending = $this->hasPendingRequest();
        $semesters = $this->getSemesters();
    @endphp

    {{-- New Request Form --}}
    @if($hasPending)
        <x-filament::section>
            <div class="flex items-center gap-3 p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                <x-heroicon-o-clock class="w-5 h-5 text-yellow-600 shrink-0" />
                <p class="text-sm text-yellow-800">لديك طلب انقطاع قيد المراجعة. لا يمكن تقديم طلب جديد حتى يتم البت في الطلب الحالي.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section heading="تقديم طلب انقطاع">
            <div class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفصل الدراسي <span class="text-red-500">*</span></label>
                    <select wire:model="semesterId"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="">-- اختر الفصل --</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                        @endforeach
                    </select>
                    @error('semesterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سبب الانقطاع <span class="text-red-500">*</span></label>
                    <textarea wire:model="reason" rows="4"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="اكتب سبباً واضحاً ومفصلاً لطلب الانقطاع..."></textarea>
                    @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    إرسال الطلب
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- History --}}
    @if($history->isNotEmpty())
        <x-filament::section heading="سجل الطلبات">
            <div class="space-y-3">
                @foreach($history as $request)
                    <div @class([
                        'p-4 rounded-lg border',
                        'border-yellow-200 bg-yellow-50' => $request->status->value === 'pending',
                        'border-green-200 bg-green-50' => $request->status->value === 'approved',
                        'border-red-200 bg-red-50' => $request->status->value === 'rejected',
                    ])>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium">{{ $request->semester->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $request->created_at->format('Y/m/d') }}</p>
                            </div>
                            <span @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                'bg-yellow-100 text-yellow-800' => $request->status->value === 'pending',
                                'bg-green-100 text-green-800' => $request->status->value === 'approved',
                                'bg-red-100 text-red-800' => $request->status->value === 'rejected',
                            ])>{{ $request->status->label() }}</span>
                        </div>
                        <p class="text-sm text-gray-700 mt-2">{{ $request->reason }}</p>
                        @if($request->decision_notes)
                            <p class="text-xs text-gray-500 mt-2 border-t pt-2">ملاحظة الإدارة: {{ $request->decision_notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
