<x-filament-panels::page>
    @php
        $registration = $this->getRegistration();
        $isEligible = $this->isEligible();
        $student = auth()->user()->student;
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- Eligibility Status --}}
    @php
        $breakdown = $student?->getThesisEligibilityBreakdown() ?? ['general' => ['required' => 0, 'completed' => 0], 'specialized' => ['required' => 0, 'completed' => 0], 'elective' => ['required' => 2, 'completed' => 0]];
        $gpaOk = (float) ($student?->gpa ?? 0) >= 3.0;
        $typeLabels = ['general' => 'المقررات العامة', 'specialized' => 'المقررات التخصصية', 'elective' => 'المقررات الاختيارية'];
    @endphp
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-shield-check style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                <span>حالة التأهل</span>
            </div>
        </x-slot>

        <div @class([
            'flex items-start gap-4 p-4 rounded-xl border',
            'bg-success-50 border-success-200 dark:bg-success-500/10 dark:border-success-500/30' => $isEligible,
            'bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30' => ! $isEligible,
        ])>
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-success-100 dark:bg-success-500/20' => $isEligible,
                'bg-danger-100 dark:bg-danger-500/20' => ! $isEligible,
            ])>
                @if($isEligible)
                    <x-heroicon-o-check style="width: 1.25rem; height: 1.25rem;" class="text-success-600 dark:text-success-400" />
                @else
                    <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                @endif
            </div>
            <div class="flex-1">
                @if($isEligible)
                    <p class="font-semibold text-success-800 dark:text-success-300 text-sm">مؤهل لتسجيل الرسالة</p>
                    <p class="text-sm text-success-700 dark:text-success-400 mt-0.5">أتممت جميع الشروط المطلوبة للتسجيل</p>
                @else
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">غير مؤهل حالياً</p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">يُشترط إتمام جميع المقررات العامة والتخصصية ومقررتين اختياريتين بمعدل 3.0 أو أعلى</p>
                @endif

                @if($student)
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach(['general', 'specialized', 'elective'] as $type)
                            @php
                                $b = $breakdown[$type];
                                $done = $b['completed'] >= $b['required'];
                            @endphp
                            <div @class([
                                'flex items-center justify-between p-3 rounded-lg border text-sm',
                                'border-success-200 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/5' => $done,
                                'border-gray-200 bg-white dark:border-white/10 dark:bg-white/5' => ! $done,
                            ])>
                                <div class="flex items-center gap-2">
                                    @if($done)
                                        <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" class="text-success-500 shrink-0" />
                                    @else
                                        <x-heroicon-o-clock style="width: 1rem; height: 1rem;" class="text-gray-400 shrink-0" />
                                    @endif
                                    <span class="text-gray-700 dark:text-gray-300">{{ $typeLabels[$type] }}</span>
                                </div>
                                <span @class([
                                    'font-bold tabular-nums',
                                    'text-success-600 dark:text-success-400' => $done,
                                    'text-gray-900 dark:text-white' => ! $done,
                                ])>{{ $b['completed'] }}/{{ $b['required'] }}</span>
                            </div>
                        @endforeach

                        {{-- GPA row --}}
                        <div @class([
                            'flex items-center justify-between p-3 rounded-lg border text-sm',
                            'border-success-200 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/5' => $gpaOk,
                            'border-gray-200 bg-white dark:border-white/10 dark:bg-white/5' => ! $gpaOk,
                        ])>
                            <div class="flex items-center gap-2">
                                @if($gpaOk)
                                    <x-heroicon-o-check-circle style="width: 1rem; height: 1rem;" class="text-success-500 shrink-0" />
                                @else
                                    <x-heroicon-o-clock style="width: 1rem; height: 1rem;" class="text-gray-400 shrink-0" />
                                @endif
                                <span class="text-gray-700 dark:text-gray-300">المعدل التراكمي</span>
                            </div>
                            <span @class([
                                'font-bold tabular-nums',
                                'text-success-600 dark:text-success-400' => $gpaOk,
                                'text-gray-900 dark:text-white' => ! $gpaOk,
                            ])>{{ number_format((float) $student->gpa, 2) }}/3.0</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Existing Registration --}}
    @if($registration)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>طلب التسجيل</span>
                </div>
            </x-slot>

            @php
                $statusConfig = [
                    'pending'  => ['label' => 'قيد المراجعة', 'color' => 'warning'],
                    'approved' => ['label' => 'مقبول',        'color' => 'success'],
                    'rejected' => ['label' => 'مرفوض',        'color' => 'danger'],
                ];
                $config = $statusConfig[$registration->status] ?? ['label' => $registration->status, 'color' => 'gray'];
            @endphp

            <div class="flex justify-between items-center mb-5">
                <span @class([
                    'inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium',
                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $config['color'] === 'warning',
                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $config['color'] === 'success',
                    'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'   => $config['color'] === 'danger',
                    'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400'           => $config['color'] === 'gray',
                ])>{{ $config['label'] }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $registration->created_at->format('Y/m/d') }}</span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">عنوان الرسالة</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->title }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">اسم المشرف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->supervisor_name }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">الدرجة العلمية</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->supervisor_degree }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">جهة عمل المشرف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->supervisor_employer }}</dd>
                </div>
            </dl>
        </x-filament::section>

    {{-- Registration Form --}}
    @elseif($isEligible)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-pencil-square style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>نموذج تسجيل الرسالة</span>
                </div>
            </x-slot>

            <div class="space-y-4 max-w-lg">
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        عنوان الرسالة <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('thesisTitle')">
                        <x-filament::input wire:model="thesisTitle" type="text" placeholder="أدخل عنوان الرسالة المقترح" />
                    </x-filament::input.wrapper>
                    @error('thesisTitle') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        اسم المشرف <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('supervisorName')">
                        <x-filament::input wire:model="supervisorName" type="text" placeholder="الاسم الكامل للمشرف" />
                    </x-filament::input.wrapper>
                    @error('supervisorName') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        الدرجة العلمية للمشرف <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('supervisorDegree')">
                        <x-filament::input wire:model="supervisorDegree" type="text" placeholder="مثال: دكتوراه، أستاذ مشارك" />
                    </x-filament::input.wrapper>
                    @error('supervisorDegree') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        جهة عمل المشرف <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('supervisorEmployer')">
                        <x-filament::input wire:model="supervisorEmployer" type="text" placeholder="الجامعة أو المؤسسة" />
                    </x-filament::input.wrapper>
                    @error('supervisorEmployer') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    إرسال طلب التسجيل
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
