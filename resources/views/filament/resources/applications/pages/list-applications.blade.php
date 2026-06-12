<x-filament-panels::page>
    @php
        $activeSpec = $this->activeSpecialization;
        $tabs = collect($this->getSpecializationTabs());
    @endphp

    <div class="flex items-center justify-start">
        <x-admin.filter-tabs :tabs="$tabs" :active="$activeSpec" wire-method="filterBySpecialization" />
    </div>

    @if (! $this->scheduledExamDate)
        <div class="my-4 flex items-start gap-4 rounded-xl border bg-warning-50 p-4 dark:bg-warning-500/10 border-warning-200 dark:border-warning-500/30">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-500/20">
                <x-heroicon-o-clock class="text-warning-600 dark:text-warning-400" style="width:1.25rem;height:1.25rem" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-warning-800 dark:text-warning-300">موعد الامتحان غير محدد</p>
                <p class="mt-0.5 text-sm text-warning-700 dark:text-warning-400">
                    يجب تحديد موعد الامتحان التفاضلي قبل قبول أي طالب قبولاً مبدئياً.
                    استخدم زر «تحديد موعد الامتحان» الموجود أعلى الصفحة.
                </p>
            </div>
        </div>
    @endif

    {{ $this->content }}
</x-filament-panels::page>
