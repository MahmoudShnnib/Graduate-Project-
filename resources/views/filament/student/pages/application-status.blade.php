<x-filament-panels::page>
    @php
        $application = $this->getApplication();
    @endphp

    @if(! $application)
        <x-filament::section>
            <p class="text-center text-gray-500 py-4">لا يوجد طلب قبول مسجل لهذا الحساب.</p>
        </x-filament::section>
    @else
        <x-filament::section heading="بيانات الطلب">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">تاريخ التقديم</dt>
                    <dd class="font-semibold">{{ $application->created_at->format('Y/m/d') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">حالة الطلب</dt>
                    <dd>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'accepted' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'exam_scheduled' => 'bg-blue-100 text-blue-800',
                            ];
                            $statusLabels = [
                                'pending' => 'قيد المراجعة',
                                'accepted' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'exam_scheduled' => 'تم تحديد موعد الاختبار',
                            ];
                            $color = $statusColors[$application->status] ?? 'bg-gray-100 text-gray-800';
                            $label = $statusLabels[$application->status] ?? $application->status;
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ $label }}
                        </span>
                    </dd>
                </div>
                @if($application->exam_date)
                    <div>
                        <dt class="text-gray-500">موعد الاختبار</dt>
                        <dd class="font-semibold">{{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}</dd>
                    </div>
                @endif
                @if($application->rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">سبب الرفض</dt>
                        <dd class="text-red-600 mt-1">{{ $application->rejection_reason }}</dd>
                    </div>
                @endif
                @if($application->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">ملاحظات</dt>
                        <dd class="mt-1">{{ $application->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-filament::section>

        {{-- Timeline --}}
        <x-filament::section heading="مسار الطلب">
            <ol class="relative border-r border-gray-200 me-3 space-y-6">
                <li class="ms-6">
                    <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary-100">
                        <x-heroicon-o-check class="w-3 h-3 text-primary-600" />
                    </span>
                    <h3 class="font-medium text-sm">تقديم الطلب</h3>
                    <p class="text-xs text-gray-500">{{ $application->created_at->diffForHumans() }}</p>
                </li>
                @if(in_array($application->status, ['exam_scheduled', 'accepted', 'rejected']))
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                            <x-heroicon-o-eye class="w-3 h-3 text-blue-600" />
                        </span>
                        <h3 class="font-medium text-sm">مراجعة الطلب</h3>
                    </li>
                @endif
                @if($application->status === 'exam_scheduled' && $application->exam_date)
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                            <x-heroicon-o-calendar class="w-3 h-3 text-blue-600" />
                        </span>
                        <h3 class="font-medium text-sm">تم تحديد موعد الاختبار</h3>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($application->exam_date)->format('Y/m/d H:i') }}</p>
                    </li>
                @endif
                @if($application->status === 'accepted')
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-green-100">
                            <x-heroicon-o-check-circle class="w-3 h-3 text-green-600" />
                        </span>
                        <h3 class="font-medium text-sm text-green-700">تم قبول الطلب</h3>
                    </li>
                @endif
                @if($application->status === 'rejected')
                    <li class="ms-6">
                        <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                            <x-heroicon-o-x-circle class="w-3 h-3 text-red-600" />
                        </span>
                        <h3 class="font-medium text-sm text-red-700">تم رفض الطلب</h3>
                    </li>
                @endif
            </ol>
        </x-filament::section>
    @endif
</x-filament-panels::page>
