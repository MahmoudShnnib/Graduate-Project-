<x-filament-panels::page>
    @php
        $student = $this->getStudent();
        $latestRequest = $this->getLatestRequest();
        $canSubmit = $this->canSubmitNewRequest();
        $hasPending = $this->hasPendingRequest();
        $isBanned = $this->isBannedFromReapply();
    @endphp

    @if(! $student)
        <x-filament::section>
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400 text-sm">لا يوجد سجل طالب مرتبط بهذا الحساب.</p>
            </div>
        </x-filament::section>
    @else
        {{-- Summon message banner --}}
        @if($latestRequest && $latestRequest->summon_message && $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Summoned)
            <div class="flex items-start gap-4 p-5 rounded-xl border-2 border-info-300 bg-info-50 dark:bg-info-500/10 dark:border-info-500/40 animate-pulse-slow">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-info-100 dark:bg-info-500/20">
                    <x-heroicon-o-megaphone style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                </div>
                <div class="flex-1">
                    <p class="font-bold text-info-800 dark:text-info-300 text-sm">تم استدعاؤك من قبل الإدارة</p>
                    <p class="text-sm text-info-700 dark:text-info-400 mt-1">{{ $latestRequest->summon_message }}</p>
                    <p class="text-xs text-info-500 dark:text-info-500 mt-2">{{ $latestRequest->summon_sent_at?->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        @endif

        {{-- Banned permanently --}}
        @if($isBanned)
            <x-filament::section>
                <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                        <x-heroicon-o-no-symbol style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">لا يمكنك تقديم طلب استثنائي جديد</p>
                        <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">تم منحك فرصة استثنائية سابقاً ولم يتم استيفاء الشروط. لا يمكن تقديم طلب آخر.</p>
                    </div>
                </div>
            </x-filament::section>

        {{-- Pending request --}}
        @elseif($hasPending)
            <x-filament::section>
                <div class="flex items-start gap-4 p-4 rounded-xl border bg-warning-50 border-warning-200 dark:bg-warning-500/10 dark:border-warning-500/30">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                        <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-warning-800 dark:text-warning-300 text-sm">طلبك قيد المراجعة</p>
                        <p class="text-sm text-warning-700 dark:text-warning-400 mt-0.5">
                            تم تقديم طلبك بتاريخ {{ $latestRequest->created_at->format('Y-m-d') }}. سيتم إشعارك عند صدور القرار.
                        </p>
                    </div>
                </div>
            </x-filament::section>

        {{-- Can submit form --}}
        @elseif($canSubmit)
            <x-filament::section>
                <x-slot name="heading">تقديم طلب استثنائي</x-slot>
                <x-slot name="description">اكتب سبب طلبك بالتفصيل. سيتم مراجعته من قبل الإدارة.</x-slot>

                <form wire:submit="submit" class="space-y-4">
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">سبب الطلب</label>
                        <textarea
                            wire:model="reason"
                            id="reason"
                            rows="5"
                            class="w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="اشرح سبب طلبك الاستثنائي بالتفصيل..."
                        ></textarea>
                        @error('reason')
                            <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-filament::button type="submit" icon="heroicon-m-paper-airplane">
                        تقديم الطلب
                    </x-filament::button>
                </form>
            </x-filament::section>

        {{-- Last request was rejected — show rejection info + allow resubmit indicator --}}
        @elseif($latestRequest && $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Rejected)
            <x-filament::section>
                <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                        <x-heroicon-o-x-circle style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">تم رفض طلبك السابق</p>
                        <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">
                            تم رفض الطلب بتاريخ {{ $latestRequest->decided_at?->format('Y-m-d') ?? '—' }}.
                        </p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">يمكنك تقديم طلب جديد. اضغط على الزر أدناه.</p>
            </x-filament::section>
        @endif

        {{-- Request history --}}
        @if($student->exceptionalRequests->count() > 0)
            <x-filament::section>
                <x-slot name="heading">سجل الطلبات</x-slot>

                <div class="space-y-3">
                    @foreach($student->exceptionalRequests()->latest()->get() as $request)
                        <div @class([
                            'p-4 rounded-lg border',
                            'border-warning-200 bg-warning-50/50 dark:bg-warning-500/5 dark:border-warning-500/20' => $request->status === \App\Enums\ExceptionalRequestStatus::Pending,
                            'border-success-200 bg-success-50/50 dark:bg-success-500/5 dark:border-success-500/20' => $request->status === \App\Enums\ExceptionalRequestStatus::Accepted,
                            'border-danger-200 bg-danger-50/50 dark:bg-danger-500/5 dark:border-danger-500/20' => $request->status === \App\Enums\ExceptionalRequestStatus::Rejected,
                            'border-info-200 bg-info-50/50 dark:bg-info-500/5 dark:border-info-500/20' => $request->status === \App\Enums\ExceptionalRequestStatus::Summoned,
                        ])>
                            <div class="flex items-center justify-between mb-2">
                                <span @class([
                                    'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $request->status === \App\Enums\ExceptionalRequestStatus::Pending,
                                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $request->status === \App\Enums\ExceptionalRequestStatus::Accepted,
                                    'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $request->status === \App\Enums\ExceptionalRequestStatus::Rejected,
                                    'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $request->status === \App\Enums\ExceptionalRequestStatus::Summoned,
                                ])>
                                    {{ $request->status->label() }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $request->created_at->format('Y-m-d') }}</span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
