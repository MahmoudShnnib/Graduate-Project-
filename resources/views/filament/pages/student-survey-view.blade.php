<x-filament-panels::page>
    @php
        $entries = $this->getSurveyEntries();
        $retakeEntries = $this->getRetakeEntries();
        $selectedCount = count($this->selected);
    @endphp

    {{-- Student Info --}}
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-8">
            <x-admin.info-pair label="اسم الطالب">{{ $this->student?->user?->name }}</x-admin.info-pair>
            <x-admin.info-pair label="رقم القيد" mono>{{ $this->student?->enrollment_no }}</x-admin.info-pair>
            <x-admin.info-pair label="التخصص">{{ $this->student?->specialization?->name }}</x-admin.info-pair>
            <x-admin.info-pair label="الفصل الدراسي">{{ $this->semester?->name }}</x-admin.info-pair>
        </div>
    </x-filament::section>

    {{-- Entries --}}
    @if($entries->isEmpty())
        <x-admin.empty-state icon="heroicon-o-clipboard-document-list" message="لم يقدم هذا الطالب أي اختيارات لهذا الفصل." />
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            x-data
                            x-on:change="
                                document.querySelectorAll('.survey-cb').forEach(cb => {
                                    cb.checked = $event.target.checked;
                                    cb.dispatchEvent(new Event('change'));
                                });
                            "
                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                        />
                        <span>اختيارات المقررات ({{ $entries->count() }})</span>
                    </label>
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($entries as $entry)
                    @php
                        $isEnrolled = $entry->enrollment !== null;
                        $isRejected = $entry->survey->rejected_at !== null;
                    @endphp

                    <div @class([
                        'flex items-center gap-4 rounded-xl border p-4 transition',
                        'border-green-200 bg-green-50/50 dark:border-green-500/20 dark:bg-green-500/5' => $isEnrolled,
                        'border-red-200 bg-red-50/40 dark:border-red-500/20 dark:bg-red-500/5' => $isRejected && ! $isEnrolled,
                        'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $isEnrolled && ! $isRejected,
                    ])>
                        {{-- Checkbox (disabled for rejected rows so bulk-enroll doesn't pick them) --}}
                        <input
                            type="checkbox"
                            value="{{ $entry->course->id }}"
                            wire:model="selected"
                            @disabled($isRejected)
                            class="survey-cb rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 shrink-0 disabled:opacity-40 disabled:cursor-not-allowed"
                        />

                        {{-- Priority dot --}}
                        <x-admin.priority-dot :value="$entry->survey->priority" />

                        {{-- Course info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $entry->course?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="font-mono">{{ $entry->course?->code }}</span>
                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                {{ $entry->course?->credits ?? 0 }} وحدة
                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                @if($entry->offering)
                                    <span class="text-green-600 dark:text-green-400">مفتوح</span>
                                @else
                                    <span class="text-gray-400">غير مفتوح</span>
                                @endif
                            </p>
                        </div>

                        {{-- Status + individual actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            @if($isEnrolled)
                                <x-admin.badge color="success">مسجّل</x-admin.badge>
                                <button wire:click="unenrollStudent({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من إلغاء تسجيل الطالب؟"
                                    class="rounded-lg bg-red-50 dark:bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition">
                                    إلغاء
                                </button>
                            @elseif($isRejected)
                                <x-admin.badge color="danger">مرفوض</x-admin.badge>
                            @else
                                <button wire:click="enrollStudent({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من تسجيل الطالب؟"
                                    class="rounded-lg bg-primary-50 dark:bg-primary-500/10 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-500/20 transition">
                                    تسجيل
                                </button>
                                <button wire:click="rejectCourse({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من رفض هذا الاختيار للطالب؟"
                                    class="rounded-lg bg-red-50 dark:bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition">
                                    رفض
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Bulk action bar --}}
            <x-admin.action-bar :selected-count="$selectedCount" selected-label="تم تحديد" empty-label="حدد المقررات لتنفيذ إجراء" class="mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
                <x-filament::button wire:click="rejectSelected" wire:confirm="هل أنت متأكد من رفض المقررات المحددة؟"
                    color="danger" icon="heroicon-o-x-mark" size="sm">
                    رفض المحددة
                </x-filament::button>
                <x-filament::button wire:click="enrollSelected" wire:confirm="هل أنت متأكد من تسجيل المقررات المحددة؟"
                    color="success" icon="heroicon-o-check" size="sm">
                    تسجيل المحددة
                </x-filament::button>
            </x-admin.action-bar>
        </x-filament::section>
    @endif

    {{-- Retake Requests --}}
    @if($retakeEntries->isNotEmpty())
        @php
            $retakeActionId = $this->retakeActionCourseId;
            $retakeActionType = $this->retakeActionType;
            $retakeActionCourse = $retakeActionId ? $retakeEntries->firstWhere(fn ($e) => $e->course->id === $retakeActionId)?->course : null;
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-arrow-path style="width: 1.1rem; height: 1.1rem;" class="text-warning-500" />
                    <span>طلبات إعادة مواد</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                        {{ $retakeEntries->count() }} طلب
                    </span>
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($retakeEntries as $entry)
                    @php
                        $isEnrolled = $entry->enrollment !== null;
                        $isRejected = $entry->survey->rejected_at !== null;
                    @endphp

                    <div @class([
                        'flex items-center gap-4 rounded-xl border p-4 transition',
                        'border-success-200 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/5' => $isEnrolled,
                        'border-danger-200 bg-danger-50/40 dark:border-danger-500/20 dark:bg-danger-500/5' => $isRejected && ! $isEnrolled,
                        'border-warning-200 bg-warning-50/50 dark:border-warning-500/20 dark:bg-warning-500/5' => ! $isEnrolled && ! $isRejected,
                    ])>
                        <div @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                            'bg-success-100 dark:bg-success-500/20' => $isEnrolled,
                            'bg-danger-100 dark:bg-danger-500/20' => $isRejected,
                            'bg-warning-100 dark:bg-warning-500/20' => ! $isEnrolled && ! $isRejected,
                        ])>
                            @if($isEnrolled)
                                <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" class="text-success-600 dark:text-success-400" />
                            @elseif($isRejected)
                                <x-heroicon-o-x-circle style="width: 1rem; height: 1rem;" class="text-danger-600 dark:text-danger-400" />
                            @else
                                <x-heroicon-o-arrow-path style="width: 1rem; height: 1rem;" class="text-warning-600 dark:text-warning-400" />
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $entry->course?->name ?? '—' }}</p>
                                <x-admin.badge color="warning">إعادة</x-admin.badge>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="font-mono">{{ $entry->course?->code }}</span>
                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                {{ $entry->course?->credits ?? 0 }} وحدة
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($isEnrolled)
                                <x-admin.badge color="success">تمت الموافقة — مسجّل</x-admin.badge>
                            @elseif($isRejected)
                                <x-admin.badge color="danger">مرفوض</x-admin.badge>
                            @else
                                <button wire:click="showRetakeAction({{ $entry->course->id }}, 'approve')"
                                    class="rounded-lg bg-success-50 dark:bg-success-500/10 px-3 py-1.5 text-xs font-medium text-success-600 dark:text-success-400 hover:bg-success-100 dark:hover:bg-success-500/20 transition">
                                    موافقة وتسجيل
                                </button>
                                <button wire:click="showRetakeAction({{ $entry->course->id }}, 'reject')"
                                    class="rounded-lg bg-danger-50 dark:bg-danger-500/10 px-3 py-1.5 text-xs font-medium text-danger-600 dark:text-danger-400 hover:bg-danger-100 dark:hover:bg-danger-500/20 transition">
                                    رفض
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Retake action confirmation modal --}}
        @if($retakeActionCourse)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="cancelRetakeAction">
                <div class="w-full max-w-md mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-white/10 overflow-hidden">
                    <div class="p-6 text-center">
                        @if($retakeActionType === 'approve')
                            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-success-100 dark:bg-success-500/20 mx-auto mb-4">
                                <x-heroicon-o-check-circle style="width: 1.75rem; height: 1.75rem;" class="text-success-600 dark:text-success-400" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">الموافقة على إعادة مادة</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">هل تريد الموافقة على إعادة الطالب لمادة:</p>
                        @else
                            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-danger-100 dark:bg-danger-500/20 mx-auto mb-4">
                                <x-heroicon-o-x-circle style="width: 1.75rem; height: 1.75rem;" class="text-danger-600 dark:text-danger-400" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">رفض طلب إعادة</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">هل تريد رفض طلب إعادة مادة:</p>
                        @endif
                        <p class="text-base font-semibold text-gray-900 dark:text-white mb-1">{{ $retakeActionCourse->name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mb-4">{{ $retakeActionCourse->code }}</p>

                        @if($retakeActionType === 'approve')
                            <div class="rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 p-3 text-sm text-start mb-2">
                                <p class="font-medium text-warning-800 dark:text-warning-300 mb-1">تنبيه:</p>
                                <ul class="text-xs text-warning-700 dark:text-warning-400 space-y-1 list-disc list-inside">
                                    <li>الطالب سبق أن اجتاز هذه المادة</li>
                                    <li>الموافقة ستسجله مباشرة في المقرر</li>
                                    <li>الدرجة الجديدة ستحل محل القديمة في المعدل</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10">
                        <x-filament::button wire:click="cancelRetakeAction" color="gray" class="flex-1">
                            إلغاء
                        </x-filament::button>
                        @if($retakeActionType === 'approve')
                            <x-filament::button wire:click="confirmRetakeAction" color="success" icon="heroicon-o-check" class="flex-1">
                                موافقة وتسجيل
                            </x-filament::button>
                        @else
                            <x-filament::button wire:click="confirmRetakeAction" color="danger" icon="heroicon-o-x-mark" class="flex-1">
                                رفض الطلب
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif

    <a href="{{ url('/admin/courses-survey-results') }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لنتائج استطلاع المقررات
    </a>
</x-filament-panels::page>
