<x-filament-panels::page>
    @php
        $isDiscussionCompleted = $this->isDiscussionCompleted();
        $thesis = auth()->user()->student?->latestThesisRegistration;
        $discussion = auth()->user()->student?->latestDiscussionRequest;
        $student = auth()->user()->student;
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- Prerequisites Check --}}
    @if(! $isDiscussionCompleted)
        <x-filament::section>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                    <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem;" class="text-warning-600 dark:text-warning-400" />
                </div>
                <div>
                    <p class="font-semibold text-warning-800 dark:text-warning-300 text-sm">الاستماحة غير متاحة بعد</p>
                    <p class="text-sm text-warning-700 dark:text-warning-400 mt-0.5">يجب إتمام المناقشة والتجليد والتسليم قبل طباعة نموذج الاستماحة.</p>
                    @if($discussion)
                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="text-warning-700 dark:text-warning-400">حالة المناقشة:</span>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Pending, \App\Enums\DiscussionStatus::AcceptedWithEdits]),
                                'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Scheduled, \App\Enums\DiscussionStatus::Discussed]),
                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $discussion->status === \App\Enums\DiscussionStatus::DiscussionApproved,
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => in_array($discussion->status, [\App\Enums\DiscussionStatus::Rejected, \App\Enums\DiscussionStatus::DiscussionRejected]),
                            ])>{{ $discussion->status->studentLabel() }}</span>
                        </div>
                    @elseif($thesis && $thesis->status === \App\Enums\ThesisStatus::Approved)
                        <p class="text-sm text-warning-600 dark:text-warning-500 mt-1">رسالتك مقبولة — يرجى تقديم طلب المناقشة أولاً.</p>
                    @else
                        <p class="text-sm text-warning-600 dark:text-warning-500 mt-1">لم تقدم طلب تسجيل رسالة بعد أو لم يتم قبولها.</p>
                    @endif
                </div>
            </div>
        </x-filament::section>

    {{-- Discussion completed — show print button --}}
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-check-badge style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>نموذج الاستماحة</span>
                    </div>
                    <a href="{{ route('print.clearance-form', ['student' => $student]) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        <x-heroicon-o-printer style="width:1rem;height:1rem" />
                        طباعة نموذج الاستماحة
                    </a>
                </div>
            </x-slot>

            <div class="flex flex-col items-center text-center py-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20 mb-4">
                    <x-heroicon-o-check-badge style="width: 2rem; height: 2rem;" class="text-success-600 dark:text-success-400" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">تم إتمام جميع المتطلبات</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">يمكنك الآن طباعة نموذج الاستماحة وتقديمه للجهات المختصة.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
