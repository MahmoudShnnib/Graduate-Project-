<x-filament-panels::page>
    @php
        $entries = $this->getSurveyEntries();
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
                    @php $isEnrolled = $entry->enrollment !== null; @endphp

                    <div @class([
                        'flex items-center gap-4 rounded-xl border p-4 transition',
                        'border-green-200 bg-green-50/50 dark:border-green-500/20 dark:bg-green-500/5' => $isEnrolled,
                        'border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900' => ! $isEnrolled,
                    ])>
                        {{-- Checkbox --}}
                        <input
                            type="checkbox"
                            value="{{ $entry->course->id }}"
                            wire:model="selected"
                            class="survey-cb rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 shrink-0"
                        />

                        {{-- Priority dot --}}
                        <x-admin.priority-dot :value="$entry->survey->priority" />

                        {{-- Course info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $entry->course?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="font-mono">{{ $entry->course?->code }}</span>
                                <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                                {{ $entry->course?->credits ?? 0 }} ساعة
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
                            @else
                                <button wire:click="enrollStudent({{ $entry->course->id }})"
                                    wire:confirm="هل أنت متأكد من تسجيل الطالب؟"
                                    class="rounded-lg bg-primary-50 dark:bg-primary-500/10 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-500/20 transition">
                                    تسجيل
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Bulk action bar --}}
            <x-admin.action-bar :selected-count="$selectedCount" selected-label="تم تحديد" empty-label="حدد المقررات لتنفيذ إجراء" class="mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
                <x-filament::button wire:click="unenrollSelected" wire:confirm="هل أنت متأكد من إلغاء تسجيل المقررات المحددة؟"
                    color="danger" icon="heroicon-o-x-mark" size="sm">
                    إلغاء تسجيل المحددة
                </x-filament::button>
                <x-filament::button wire:click="enrollSelected" wire:confirm="هل أنت متأكد من تسجيل المقررات المحددة؟"
                    color="success" icon="heroicon-o-check" size="sm">
                    تسجيل المحددة
                </x-filament::button>
            </x-admin.action-bar>
        </x-filament::section>
    @endif

    <a href="{{ url('/admin/student-surveys-list') }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
        <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
        العودة لقائمة الاستطلاعات
    </a>
</x-filament-panels::page>
