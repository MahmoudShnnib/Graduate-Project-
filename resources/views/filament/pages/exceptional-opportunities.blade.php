<x-filament-panels::page>
    @php
        $withRequests = $this->getDismissedWithPendingRequest();
        $withoutRequests = $this->getDismissedWithoutRequest();
        $revived = $this->getRevivedStudents();
    @endphp

    @if($withRequests->isEmpty() && $withoutRequests->isEmpty() && $revived->isEmpty())
        <x-filament::section>
            <div class="text-center py-12">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                    <x-heroicon-o-check-circle style="width: 1.5rem; height: 1.5rem;" class="text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">لا يوجد طلاب مفصولون حالياً.</p>
            </div>
        </x-filament::section>
    @endif

    {{-- Table 1: Students with pending requests --}}
    @if($withRequests->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock style="width: 1.1rem; height: 1.1rem;" class="text-warning-500" />
                    <span>طلبات بانتظار المراجعة ({{ $withRequests->count() }})</span>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الطالب</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">سبب الفصل</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">تاريخ الفصل</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">حالة الطلب</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($withRequests as $student)
                            @php $latestRequest = $this->getLatestRequest($student); @endphp
                            <tr onclick="window.location='{{ \App\Filament\Pages\ExceptionalOpportunitiesPage::getStudentUrl($student->id) }}'" class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->enrollment_no ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    <span @class([
                                        'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                        'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $student->dismissal_type?->value === 'course_failure',
                                        'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $student->dismissal_type?->value !== 'course_failure',
                                    ])>{{ $this->getDismissalReasonText($student) }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->dismissed_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    @if($latestRequest)
                                        <span @class([
                                            'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Pending,
                                            'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Summoned,
                                        ])>{{ $latestRequest->status->label() }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Table 2: Dismissed students without pending request --}}
    @if($withoutRequests->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-group style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>طلاب مفصولون ({{ $withoutRequests->count() }})</span>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الطالب</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">سبب الفصل</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">تاريخ الفصل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($withoutRequests as $student)
                            <tr onclick="window.location='{{ \App\Filament\Pages\ExceptionalOpportunitiesPage::getStudentUrl($student->id) }}'" class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->enrollment_no ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->specialization?->name ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    <span @class([
                                        'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                        'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $student->dismissal_type?->value === 'course_failure',
                                        'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $student->dismissal_type?->value !== 'course_failure',
                                    ])>{{ $this->getDismissalReasonText($student) }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->dismissed_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Table 3: Revived students (previously dismissed, now active) --}}
    @if($revived->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-path style="width: 1.1rem; height: 1.1rem;" class="text-success-500" />
                    <span>طلاب أُعيدوا بعد الفصل ({{ $revived->count() }})</span>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الطالب</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الحالة الحالية</th>
                            <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">تاريخ القبول</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($revived as $student)
                            @php $acceptedRequest = $student->exceptionalRequests->first(); @endphp
                            <tr onclick="window.location='{{ \App\Filament\Pages\ExceptionalOpportunitiesPage::getStudentUrl($student->id) }}'" class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->enrollment_no ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->specialization?->name ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                        {{ $student->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $acceptedRequest?->decided_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
