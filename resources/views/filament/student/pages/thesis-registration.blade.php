<x-filament-panels::page>
    @php
        $registration = $this->getRegistration();
        $isEligible = $this->isEligible();
        $student = auth()->user()->student;
    @endphp

    {{-- Eligibility Status --}}
    <x-filament::section heading="حالة التأهل">
        <div class="flex items-start gap-4">
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-green-100' => $isEligible,
                'bg-red-100' => ! $isEligible,
            ])>
                @if($isEligible)
                    <x-heroicon-o-check class="w-5 h-5 text-green-600" />
                @else
                    <x-heroicon-o-x-mark class="w-5 h-5 text-red-600" />
                @endif
            </div>
            <div>
                @if($isEligible)
                    <p class="font-medium text-green-700">مؤهل لتسجيل الرسالة</p>
                    <p class="text-sm text-gray-500 mt-0.5">أتممت الشروط المطلوبة للتسجيل</p>
                @else
                    <p class="font-medium text-red-700">غير مؤهل حالياً</p>
                    <p class="text-sm text-gray-500 mt-0.5">يُشترط إتمام 10 مقررات على الأقل وتحقيق معدل 3.0 أو أعلى</p>
                    @if($student)
                        <div class="mt-2 text-sm text-gray-600">
                            <span>المقررات المجتازة: <strong>{{ $student->completedCoursesCount() }}/10</strong></span>
                            <span class="mx-3">|</span>
                            <span>المعدل: <strong>{{ number_format((float) $student->gpa, 2) }}/3.0</strong></span>
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
                $statusColors = ['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red'];
                $statusLabels = ['pending' => 'قيد المراجعة', 'approved' => 'مقبول', 'rejected' => 'مرفوض'];
                $color = $statusColors[$registration->status] ?? 'gray';
                $label = $statusLabels[$registration->status] ?? $registration->status;
            @endphp
            <div class="flex justify-between items-start mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                    {{ $label }}
                </span>
                <span class="text-xs text-gray-500">{{ $registration->created_at->format('Y/m/d') }}</span>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">عنوان الرسالة</dt>
                    <dd class="font-medium mt-0.5">{{ $registration->title }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">اسم المشرف</dt>
                    <dd class="font-medium mt-0.5">{{ $registration->supervisor_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">الدرجة العلمية</dt>
                    <dd class="font-medium mt-0.5">{{ $registration->supervisor_degree }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">جهة عمل المشرف</dt>
                    <dd class="font-medium mt-0.5">{{ $registration->supervisor_employer }}</dd>
                </div>
            </dl>
        </x-filament::section>

    {{-- Registration Form --}}
    @elseif($isEligible)
        <x-filament::section heading="نموذج تسجيل الرسالة">
            <div class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الرسالة <span class="text-red-500">*</span></label>
                    <input wire:model="thesisTitle" type="text"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="أدخل عنوان الرسالة المقترح" />
                    @error('thesisTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم المشرف <span class="text-red-500">*</span></label>
                    <input wire:model="supervisorName" type="text"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="الاسم الكامل للمشرف" />
                    @error('supervisorName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الدرجة العلمية للمشرف <span class="text-red-500">*</span></label>
                    <input wire:model="supervisorDegree" type="text"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="مثال: دكتوراه، أستاذ مشارك" />
                    @error('supervisorDegree') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">جهة عمل المشرف <span class="text-red-500">*</span></label>
                    <input wire:model="supervisorEmployer" type="text"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
                        placeholder="الجامعة أو المؤسسة" />
                    @error('supervisorEmployer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane">
                    إرسال طلب التسجيل
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
