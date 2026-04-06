<x-filament-panels::page>
    @php
        $application = $this->getApplication();
    @endphp

    @if(! $application)
        <x-filament::section>
            <div class="text-center py-12">
                <p class="text-gray-400 dark:text-gray-500 text-sm">لا يوجد طلب قبول مسجل لهذا الحساب.</p>
            </div>
        </x-filament::section>
    @else
        {{-- Status Banner --}}
        <div @class([
            'rounded-xl border p-5 flex items-center gap-4',
            'bg-warning-50 border-warning-200 dark:bg-warning-500/10 dark:border-warning-500/30' => $application->status === 'pending',
            'bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/30' => in_array($application->status, ['reviewed', 'exam_scheduled']),
            'bg-success-50 border-success-200 dark:bg-success-500/10 dark:border-success-500/30' => $application->status === 'accepted',
            'bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30' => $application->status === 'rejected',
        ])>
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-warning-100 dark:bg-warning-500/20' => $application->status === 'pending',
                'bg-blue-100 dark:bg-blue-500/20' => in_array($application->status, ['reviewed', 'exam_scheduled']),
                'bg-success-100 dark:bg-success-500/20' => $application->status === 'accepted',
                'bg-danger-100 dark:bg-danger-500/20' => $application->status === 'rejected',
            ])>
                @if($application->status === 'accepted')
                    <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                @elseif($application->status === 'rejected')
                    <x-heroicon-o-x-circle style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                @elseif($application->status === 'exam_scheduled')
                    <x-heroicon-o-calendar style="width: 1.25rem; height: 1.25rem;" class="text-blue-600 dark:text-blue-400" />
                @else
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                @endif
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">
                    @if($application->status === 'pending') قيد المراجعة
                    @elseif($application->status === 'reviewed') تمت المراجعة
                    @elseif($application->status === 'exam_scheduled') تم تحديد موعد الاختبار
                    @elseif($application->status === 'accepted') تم قبول الطلب
                    @elseif($application->status === 'rejected') تم رفض الطلب
                    @endif
                </p>
                @if($application->status === 'exam_scheduled' && $application->exam_date)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                        موعد الاختبار: {{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}
                    </p>
                @endif
                @if($application->status === 'rejected' && $application->rejection_reason)
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">{{ $application->rejection_reason }}</p>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <x-filament::section heading="مسار الطلب">
            <ol class="relative border-r border-gray-200 dark:border-white/10 me-3 space-y-6">
                <li class="ms-6">
                    <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/20">
                        <x-heroicon-o-check style="width: 0.75rem; height: 0.75rem;" class="text-primary-600 dark:text-primary-400" />
                    </span>
                    <h3 class="font-medium text-sm text-gray-900 dark:text-white">تقديم الطلب</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $application->created_at->diffForHumans() }}</p>
                </li>
                @if(in_array($application->status, ['exam_scheduled', 'accepted', 'rejected']))
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/20">
                            <x-heroicon-o-eye style="width: 0.75rem; height: 0.75rem;" class="text-blue-600 dark:text-blue-400" />
                        </span>
                        <h3 class="font-medium text-sm text-gray-900 dark:text-white">مراجعة الطلب</h3>
                    </li>
                @endif
                @if($application->status === 'exam_scheduled' && $application->exam_date)
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/20">
                            <x-heroicon-o-calendar style="width: 0.75rem; height: 0.75rem;" class="text-blue-600 dark:text-blue-400" />
                        </span>
                        <h3 class="font-medium text-sm text-gray-900 dark:text-white">تم تحديد موعد الاختبار</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}</p>
                    </li>
                @endif
                @if($application->status === 'accepted')
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                            <x-heroicon-o-check-circle style="width: 0.75rem; height: 0.75rem;" class="text-success-600 dark:text-success-400" />
                        </span>
                        <h3 class="font-medium text-sm text-success-700 dark:text-success-400">تم قبول الطلب</h3>
                    </li>
                @endif
                @if($application->status === 'rejected')
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
            <x-filament::section heading="ملاحظات">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $application->notes }}</p>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
