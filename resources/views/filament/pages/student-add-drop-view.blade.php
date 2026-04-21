<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    {{-- Student Info --}}
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-8">
            <x-admin.info-pair label="اسم الطالب">{{ $this->student?->user?->name }}</x-admin.info-pair>
            <x-admin.info-pair label="رقم القيد" mono>{{ $this->student?->enrollment_no }}</x-admin.info-pair>
            <x-admin.info-pair label="التخصص">{{ $this->student?->specialization?->name }}</x-admin.info-pair>
            <x-admin.info-pair label="الفصل الدراسي">{{ $this->semester?->name }}</x-admin.info-pair>
        </div>
    </x-filament::section>

    {{-- Progress banner --}}
    <x-filament::section>
        <div @class([
            'flex items-start gap-4 p-4 rounded-xl border',
            'bg-success-50 border-success-200 dark:bg-success-500/10 dark:border-success-500/30' => $stats['completed'],
            'bg-warning-50 border-warning-200 dark:bg-warning-500/10 dark:border-warning-500/30' => ! $stats['completed'] && $stats['pending'] > 0,
            'bg-gray-50 border-gray-200 dark:bg-white/5 dark:border-white/10' => $stats['total'] === 0,
        ])>
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-success-100 dark:bg-success-500/20' => $stats['completed'],
                'bg-warning-100 dark:bg-warning-500/20' => ! $stats['completed'] && $stats['pending'] > 0,
                'bg-gray-100 dark:bg-white/10' => $stats['total'] === 0,
            ])>
                @if($stats['completed'])
                    <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                @elseif($stats['total'] === 0)
                    <x-heroicon-o-inbox style="width: 1.25rem; height: 1.25rem;" class="text-gray-400 dark:text-gray-500" />
                @else
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                @endif
            </div>
            <div class="flex-1">
                @if($stats['completed'])
                    <p class="font-semibold text-success-800 dark:text-success-300 text-sm">تمت مراجعة جميع الطلبات</p>
                    <p class="text-sm text-success-700 dark:text-success-400 mt-0.5">
                        {{ $stats['approved'] }} مقبول · {{ $stats['rejected'] }} مرفوض
                    </p>
                @elseif($stats['total'] === 0)
                    <p class="font-semibold text-gray-700 dark:text-gray-300 text-sm">لا توجد طلبات</p>
                @else
                    <p class="font-semibold text-warning-800 dark:text-warning-300 text-sm">قيد المراجعة</p>
                    <p class="text-sm text-warning-700 dark:text-warning-400 mt-0.5">
                        {{ $stats['pending'] }} قيد المراجعة · {{ $stats['approved'] }} مقبول · {{ $stats['rejected'] }} مرفوض
                    </p>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Requests table (reuses AddDropRequestResource::table with per-request approve/reject) --}}
    {{ $this->table }}

    <a href="{{ url('/admin/add-drop-requests') }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لقائمة الطلاب
    </a>
</x-filament-panels::page>
