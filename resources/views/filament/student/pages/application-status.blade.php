<x-filament-panels::page>
    @php
        $application = $this->getApplication();
    @endphp

    @if(! $application)
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد طلب قبول مسجل لهذا الحساب.</p>
            </div>
        </x-filament::section>
    @else
        {{-- Status Banner --}}
        <div @class([
            'rounded-xl border p-5 flex items-center gap-4',
            'bg-warning-50 border-warning-200 dark:bg-warning-500/10 dark:border-warning-500/30' => $application->status->value === 'pending',
            'bg-info-50 border-info-200 dark:bg-info-500/10 dark:border-info-500/30' => $application->status->value === 'initially_approved',
            'bg-success-50 border-success-200 dark:bg-success-500/10 dark:border-success-500/30' => $application->status->value === 'accepted',
            'bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30' => $application->status->value === 'rejected',
        ])>
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-warning-100 dark:bg-warning-500/20' => $application->status->value === 'pending',
                'bg-info-100 dark:bg-info-500/20' => $application->status->value === 'initially_approved',
                'bg-success-100 dark:bg-success-500/20' => $application->status->value === 'accepted',
                'bg-danger-100 dark:bg-danger-500/20' => $application->status->value === 'rejected',
            ])>
                @if($application->status->value === 'accepted')
                    <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                @elseif($application->status->value === 'rejected')
                    <x-heroicon-o-x-circle style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                @elseif($application->status->value === 'initially_approved')
                    <x-heroicon-o-calendar style="width: 1.25rem; height: 1.25rem;" class="text-info-600 dark:text-info-400" />
                @else
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                @endif
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">
                    @if($application->status->value === 'pending') قيد المراجعة
                    @elseif($application->status->value === 'initially_approved') مقبول مبدئياً — بانتظار الامتحان
                    @elseif($application->status->value === 'accepted') تم قبول الطلب
                    @elseif($application->status->value === 'rejected') تم رفض الطلب
                    @endif
                </p>
                @if($application->status->value === 'initially_approved' && $application->exam_date)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                        موعد الاختبار: {{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}
                    </p>
                @endif
                @if($application->status->value === 'rejected' && $application->rejection_reason)
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">{{ $application->rejection_reason }}</p>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-queue-list style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>مسار الطلب</span>
                </div>
            </x-slot>

            <ol class="relative border-r border-gray-200 dark:border-white/10 me-3 space-y-6">
                <li class="ms-6">
                    <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/20">
                        <x-heroicon-o-check style="width: 0.75rem; height: 0.75rem;" class="text-primary-600 dark:text-primary-400" />
                    </span>
                    <h3 class="font-medium text-sm text-gray-900 dark:text-white">تقديم الطلب</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $application->created_at->diffForHumans() }}</p>
                </li>
                @if(in_array($application->status->value, ['initially_approved', 'accepted', 'rejected']))
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-info-100 dark:bg-info-500/20">
                            <x-heroicon-o-eye style="width: 0.75rem; height: 0.75rem;" class="text-info-600 dark:text-info-400" />
                        </span>
                        <h3 class="font-medium text-sm text-gray-900 dark:text-white">مراجعة الطلب</h3>
                    </li>
                @endif
                @if(in_array($application->status->value, ['initially_approved', 'accepted']) && $application->exam_date)
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-info-100 dark:bg-info-500/20">
                            <x-heroicon-o-calendar style="width: 0.75rem; height: 0.75rem;" class="text-info-600 dark:text-info-400" />
                        </span>
                        <h3 class="font-medium text-sm text-gray-900 dark:text-white">مقبول مبدئياً — بانتظار الامتحان</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}</p>
                    </li>
                @endif
                @if($application->status->value === 'accepted')
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                            <x-heroicon-o-check-circle style="width: 0.75rem; height: 0.75rem;" class="text-success-600 dark:text-success-400" />
                        </span>
                        <h3 class="font-medium text-sm text-success-700 dark:text-success-400">تم قبول الطلب</h3>
                    </li>
                @endif
                @if($application->status->value === 'rejected')
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                            <x-heroicon-o-x-circle style="width: 0.75rem; height: 0.75rem;" class="text-danger-600 dark:text-danger-400" />
                        </span>
                        <h3 class="font-medium text-sm text-danger-700 dark:text-danger-400">تم رفض الطلب</h3>
                    </li>
                @endif
            </ol>
        </x-filament::section>

        @if($application->notes)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chat-bubble-bottom-center-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>ملاحظات</span>
                    </div>
                </x-slot>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $application->notes }}</p>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
