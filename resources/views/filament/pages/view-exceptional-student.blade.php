<x-filament-panels::page>
    @php
        $student = $this->record;
        $latestRequest = $student->exceptionalRequests->first();
    @endphp

    {{-- Student Info --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                <span>بيانات الطالب</span>
            </div>
        </x-slot>

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">اسم الطالب</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم القيد</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->enrollment_no ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">التخصص</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->specialization?->name ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المعدل التراكمي</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ number_format((float) $student->gpa, 2) }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">البريد الالكتروني</dt>
                <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $student->user?->email ?? '—' }}</dd>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم الهاتف</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $student->user?->phone ?? '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Dismissal / Revived Info --}}
    @if($student->status === \App\Enums\StudentStatus::Dismissed)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shield-exclamation style="width: 1.1rem; height: 1.1rem;" class="text-danger-500" />
                    <span>سبب الفصل</span>
                </div>
            </x-slot>

            <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                    <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">{{ $this->getDismissalReasonText() }}</p>
                    <p class="text-xs text-danger-600 dark:text-danger-500 mt-1">تاريخ الفصل: {{ $student->dismissed_at?->format('Y-m-d') ?? '—' }}</p>

                    @if($student->activeWarnings->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-danger-200 dark:border-danger-500/30 space-y-1">
                            <p class="text-xs font-semibold text-danger-700 dark:text-danger-400">التحذيرات النشطة:</p>
                            @foreach($student->activeWarnings as $warning)
                                <div class="flex items-center gap-2 text-xs text-danger-600 dark:text-danger-400">
                                    @if($warning->type === \App\Enums\WarningType::CourseFailure)
                                        <x-heroicon-o-x-circle style="width: 0.75rem; height: 0.75rem;" class="shrink-0" />
                                        <span>رسوب: {{ $warning->course?->name ?? '—' }}</span>
                                    @else
                                        <x-heroicon-o-clock style="width: 0.75rem; height: 0.75rem;" class="shrink-0" />
                                        <span>انقطاع — {{ $warning->semester?->name ?? '' }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-check-circle style="width: 1.1rem; height: 1.1rem;" class="text-success-500" />
                    <span>تمت إعادة الطالب</span>
                </div>
            </x-slot>

            <div class="flex items-start gap-4 p-4 rounded-xl border bg-success-50 border-success-200 dark:bg-success-500/10 dark:border-success-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                    <x-heroicon-o-arrow-path style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-success-800 dark:text-success-300 text-sm">هذا الطالب كان مفصولاً وتم قبول طلبه الاستثنائي</p>
                    <p class="text-sm text-success-700 dark:text-success-400 mt-1">الحالة الحالية: {{ $student->status->label() }}</p>

                    @if($student->activeWarnings->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-success-200 dark:border-success-500/30 space-y-1">
                            <p class="text-xs font-semibold text-success-700 dark:text-success-400">التحذيرات النشطة:</p>
                            @foreach($student->activeWarnings as $warning)
                                <div class="flex items-center gap-2 text-xs text-success-600 dark:text-success-400">
                                    @if($warning->type === \App\Enums\WarningType::CourseFailure)
                                        <x-heroicon-o-x-circle style="width: 0.75rem; height: 0.75rem;" class="shrink-0" />
                                        <span>{{ $warning->course?->name ?? '—' }} {{ $warning->has_exceptional_chance ? '(فرصة أخيرة)' : '' }}</span>
                                    @else
                                        <x-heroicon-o-clock style="width: 0.75rem; height: 0.75rem;" class="shrink-0" />
                                        <span>انقطاع — {{ $warning->semester?->name ?? '' }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Request Info --}}
    @if($latestRequest)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>الطلب الاستثنائي</span>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                        'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Pending,
                        'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Summoned,
                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Accepted,
                        'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Rejected,
                    ])>{{ $latestRequest->status->label() }}</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">سبب الطلب</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $latestRequest->reason }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ $latestRequest->created_at->format('Y-m-d H:i') }}</p>
                </div>

                @if($latestRequest->summon_message)
                    <div class="p-4 rounded-xl bg-info-50 dark:bg-info-500/10 border border-info-200 dark:border-info-500/30">
                        <p class="text-xs font-semibold text-info-600 dark:text-info-400 mb-1">رسالة الاستدعاء</p>
                        <p class="text-sm text-info-700 dark:text-info-300">{{ $latestRequest->summon_message }}</p>
                        <p class="text-xs text-info-500 mt-2">{{ $latestRequest->summon_sent_at?->format('Y-m-d H:i') }}</p>
                    </div>
                @endif

            </div>
        </x-filament::section>
    @endif

    {{-- Back Link --}}
    <a href="{{ \App\Filament\Pages\ExceptionalOpportunitiesPage::getUrl() }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لقائمة الطلاب المفصولين
    </a>

</x-filament-panels::page>
