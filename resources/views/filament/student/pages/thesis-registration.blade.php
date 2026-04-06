<x-filament-panels::page>
    @php
        $registration = $this->getRegistration();
        $isEligible = $this->isEligible();
        $student = auth()->user()->student;
    @endphp

    {{-- Eligibility Status --}}
    <x-filament::section heading="حالة التأهل">
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
                    <p class="text-sm text-success-700 dark:text-success-400 mt-0.5">أتممت الشروط المطلوبة للتسجيل</p>
                @else
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">غير مؤهل حالياً</p>
                    <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">يُشترط إتمام 10 مقررات على الأقل وتحقيق معدل 3.0 أو أعلى</p>
                    @if($student)
                        <div class="mt-3 flex items-center gap-4 text-sm">
                            <div class="flex items-center gap-1.5">
                                <span class="text-gray-500 dark:text-gray-400">المقررات:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $student->completedCoursesCount() }}/10</span>
                            </div>
                            <div class="w-px h-4 bg-gray-300 dark:bg-white/20"></div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-gray-500 dark:text-gray-400">المعدل:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format((float) $student->gpa, 2) }}/3.0</span>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Existing Registration --}}
    @if($registration)
        <x-filament::section heading="طلب التسجيل">
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
        <x-filament::section heading="نموذج تسجيل الرسالة">
            <div class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">عنوان الرسالة <span class="text-danger-500">*</span></label>
                    <input wire:model="thesisTitle" type="text"
                        class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="أدخل عنوان الرسالة المقترح" />
                    @error('thesisTitle') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">اسم المشرف <span class="text-danger-500">*</span></label>
                    <input wire:model="supervisorName" type="text"
                        class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="الاسم الكامل للمشرف" />
                    @error('supervisorName') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">الدرجة العلمية للمشرف <span class="text-danger-500">*</span></label>
                    <input wire:model="supervisorDegree" type="text"
                        class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="مثال: دكتوراه، أستاذ مشارك" />
                    @error('supervisorDegree') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">جهة عمل المشرف <span class="text-danger-500">*</span></label>
                    <input wire:model="supervisorEmployer" type="text"
                        class="w-full border-gray-300 dark:border-white/10 dark:bg-gray-800 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="الجامعة أو المؤسسة" />
                    @error('supervisorEmployer') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    إرسال طلب التسجيل
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
