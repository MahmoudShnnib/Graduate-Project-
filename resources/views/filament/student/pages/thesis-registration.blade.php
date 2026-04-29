<x-filament-panels::page>
    @php
        $registration = $this->getLatestRegistration();
        $isEligible = $this->isEligible();
        $canSubmit = $this->canSubmitNew();
        $student = auth()->user()->student;
        $user = auth()->user();
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

    {{-- Existing Registration (show when not rejected, or when pending/printed/approved) --}}
    @if($registration && $registration->status !== \App\Enums\ThesisStatus::Rejected)
        @php
            $statusLabel = $registration->status->studentLabel();
            $statusColor = $registration->status->studentColor();
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-text style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>طلب التسجيل</span>
                </div>
            </x-slot>

            <div class="flex justify-between items-center mb-5">
                <span @class([
                    'inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium',
                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $statusColor === 'warning',
                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $statusColor === 'success',
                    'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'   => $statusColor === 'danger',
                ])>{{ $statusLabel }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $registration->created_at->format('Y/m/d') }}</span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">عنوان الرسالة (عربي)</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->title }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">عنوان الرسالة (إنجليزي)</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $registration->title_en ?? '—' }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10 sm:col-span-2">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المشرف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $registration->getSupervisorDisplayName() }}</dd>
                </div>
                @if($registration->proposal_path)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10 sm:col-span-2">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">المقترح البحثي</dt>
                        <dd>
                            <a href="{{ asset('storage/' . $registration->proposal_path) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                                <x-heroicon-o-document-arrow-down style="width: 1rem; height: 1rem;" />
                                تحميل الملف
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>
        </x-filament::section>
    @endif

    {{-- Rejection notice + resubmit prompt --}}
    @if($registration && $registration->status === \App\Enums\ThesisStatus::Rejected)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-x-circle style="width: 1.1rem; height: 1.1rem;" class="text-danger-500" />
                    <span>تم رفض الطلب السابق</span>
                </div>
            </x-slot>

            <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                    <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">مرفوض — {{ $registration->created_at->format('Y/m/d') }}</p>
                    @if($registration->rejection_reason)
                        <p class="text-sm text-danger-700 dark:text-danger-400 mt-1">سبب الرفض: {{ $registration->rejection_reason }}</p>
                    @endif
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">يمكنك إعادة تقديم طلب جديد أدناه.</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Registration Form --}}
    @if($isEligible && $canSubmit && $student?->canPerformActions())
        {{-- Section 1: Student Info (read-only) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>بيانات الطالب</span>
                </div>
            </x-slot>

            @php
                $completionDate = $this->getCoursesCompletionDate();
            @endphp

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">اسم الطالب</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم القيد</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $student->enrollment_no ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">مكان وتاريخ الميلاد</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $student->birth_place ?? '—' }} — {{ $student->birth_date?->format('Y/m/d') ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الرقم الوطني</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $student->national_id ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم الهاتف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $user->phone ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">البريد الالكتروني</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $user->email }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">القسم</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $student->specialization?->name ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ بداية التسجيل</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $student->created_at->format('Y/m/d') }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ الانتهاء من المقررات</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $completionDate ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المعدل التراكمي</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format((float) $student->gpa, 2) }}</dd>
                </div>
            </dl>
        </x-filament::section>

        {{-- Section 2: Thesis Title --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-pencil-square style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>عنوان الرسالة</span>
                </div>
            </x-slot>

            <div class="space-y-4 max-w-2xl">
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        عنوان الرسالة باللغة العربية <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('thesisTitle')">
                        <x-filament::input wire:model="thesisTitle" type="text" placeholder="أدخل عنوان الرسالة باللغة العربية" />
                    </x-filament::input.wrapper>
                    @error('thesisTitle') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        عنوان الرسالة باللغة الإنجليزية <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('thesisTitleEn')">
                        <x-filament::input wire:model="thesisTitleEn" type="text" dir="ltr" placeholder="Enter the thesis title in English" />
                    </x-filament::input.wrapper>
                    @error('thesisTitleEn') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-filament::section>

        {{-- Section 3: Supervisor --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-academic-cap style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>بيانات المشرف</span>
                </div>
            </x-slot>

            <div class="space-y-4 max-w-2xl">
                {{-- Supervisor type radio --}}
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model.live="supervisorType" value="internal"
                               class="fi-radio-input text-primary-600 ring-1 ring-gray-950/10 focus:ring-primary-600 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">من الجامعة</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model.live="supervisorType" value="external"
                               class="fi-radio-input text-primary-600 ring-1 ring-gray-950/10 focus:ring-primary-600 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">من خارج الجامعة</span>
                    </label>
                </div>

                {{-- Supervisor select --}}
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        اختيار المشرف <span class="text-danger-500">*</span>
                    </label>
                    <select wire:model.live="selectedSupervisorId"
                            class="fi-select-input w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">— اختر المشرف —</option>
                        @if($supervisorType === 'internal')
                            @foreach($this->getInternalSupervisors() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        @else
                            @foreach($this->getExternalSupervisors() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('selectedSupervisorId') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                {{-- Supervisor details (auto-populated, read-only) --}}
                @php $details = $this->getSelectedSupervisorDetails(); @endphp
                @if($details)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">بيانات المشرف</p>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-400 dark:text-gray-500">الاسم</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $details['name'] }}</dd>
                            </div>
                            @if(isset($details['academic_rank']))
                                <div>
                                    <dt class="text-xs text-gray-400 dark:text-gray-500">الدرجة العلمية</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $details['academic_rank'] }}</dd>
                                </div>
                            @endif
                            @if(isset($details['qualification']))
                                <div>
                                    <dt class="text-xs text-gray-400 dark:text-gray-500">المؤهل العلمي</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $details['qualification'] }}</dd>
                                </div>
                            @endif
                            @if(isset($details['institution']))
                                <div>
                                    <dt class="text-xs text-gray-400 dark:text-gray-500">الجامعة / المعهد</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $details['institution'] }}</dd>
                                </div>
                            @endif
                            @if($details['email'] ?? null)
                                <div>
                                    <dt class="text-xs text-gray-400 dark:text-gray-500">البريد الالكتروني</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $details['email'] }}</dd>
                                </div>
                            @endif
                            @if($details['phone'] ?? null)
                                <div>
                                    <dt class="text-xs text-gray-400 dark:text-gray-500">رقم الهاتف</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $details['phone'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Section 4: Proposal Upload --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-up-tray style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>مقترح البحث</span>
                </div>
            </x-slot>

            <div class="space-y-4 max-w-2xl">
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        ملف المقترح البحثي <span class="text-danger-500">*</span>
                    </label>
                    <input type="file" wire:model="proposalFile"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400
                                  file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-primary-50 file:text-primary-700
                                  dark:file:bg-primary-500/20 dark:file:text-primary-400
                                  hover:file:bg-primary-100 dark:hover:file:bg-primary-500/30
                                  cursor-pointer" />
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">الحد الأقصى لحجم الملف: 10 ميجابايت</p>
                    @error('proposalFile') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    إرسال طلب التسجيل
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- Previous registrations history --}}
    @if($student && $student->thesisRegistrations()->count() > 1)
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>الطلبات السابقة</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($student->thesisRegistrations()->latest()->skip(1)->get() as $old)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-white/10 text-sm">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $old->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $old->created_at->format('Y/m/d') }}</p>
                        </div>
                        <span @class([
                            'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                            'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $old->status->studentColor() === 'warning',
                            'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $old->status->studentColor() === 'success',
                            'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400'   => $old->status->studentColor() === 'danger',
                        ])>{{ $old->status->studentLabel() }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
