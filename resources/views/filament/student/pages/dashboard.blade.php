<x-filament-panels::page>
    @php
        $student = $this->getStudent();
        $semester = $this->getActiveSemester();
    @endphp

    @if(! $student)
        <x-filament::section>
            <p class="text-center text-gray-500">لا يوجد سجل طالب مرتبط بهذا الحساب. يرجى التواصل مع الإدارة.</p>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Student Info Card --}}
            <div class="md:col-span-2">
                <x-filament::section heading="بياناتي">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">الاسم</dt>
                            <dd class="font-semibold">{{ auth()->user()->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">رقم القيد</dt>
                            <dd class="font-semibold font-mono">{{ $student->enrollment_no ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">التخصص</dt>
                            <dd class="font-semibold">{{ $student->specialization?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">الحالة</dt>
                            <dd>
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-green-100 text-green-800' => $student->status?->value === 'active',
                                    'bg-yellow-100 text-yellow-800' => $student->status?->value === 'suspended',
                                    'bg-red-100 text-red-800' => $student->status?->value === 'withdrawn',
                                    'bg-blue-100 text-blue-800' => $student->status?->value === 'graduated',
                                ])>
                                    {{ $student->status?->label() ?? '—' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">المعدل التراكمي</dt>
                            <dd class="font-semibold text-lg text-primary-600">{{ number_format((float) $student->gpa, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">عدد التحذيرات</dt>
                            <dd class="font-semibold">{{ $student->warnings_count }}</dd>
                        </div>
                    </dl>
                </x-filament::section>
            </div>

            {{-- Active Semester --}}
            <div>
                <x-filament::section heading="الفصل الدراسي الحالي">
                    @if($semester)
                        <dl class="space-y-2 text-sm">
                            <div>
                                <dt class="text-gray-500">الفصل</dt>
                                <dd class="font-semibold">{{ $semester->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">فترة الإضافة والحذف</dt>
                                <dd>
                                    @if($semester->isAddDropOpen())
                                        <span class="text-green-600 font-medium">مفتوحة</span>
                                    @else
                                        <span class="text-red-500">مغلقة</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-gray-500 text-sm">لا يوجد فصل دراسي نشط حالياً</p>
                    @endif
                </x-filament::section>
            </div>
        </div>

        {{-- Quick Links --}}
        <x-filament::section heading="الخدمات الإلكترونية">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <a href="{{ route('filament.student.pages.course-survey-page') }}" wire:navigate
                   class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-colors text-center">
                    <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-primary-600" />
                    <span class="text-sm font-medium">استطلاع المقررات</span>
                </a>
                <a href="{{ route('filament.student.pages.student-courses-page') }}" wire:navigate
                   class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-colors text-center">
                    <x-heroicon-o-academic-cap class="w-6 h-6 text-primary-600" />
                    <span class="text-sm font-medium">مقرراتي</span>
                </a>
                <a href="{{ route('filament.student.pages.grades-page') }}" wire:navigate
                   class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-colors text-center">
                    <x-heroicon-o-chart-bar class="w-6 h-6 text-primary-600" />
                    <span class="text-sm font-medium">الدرجات</span>
                </a>
                <a href="{{ route('filament.student.pages.thesis-registration-page') }}" wire:navigate
                   class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-colors text-center">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-600" />
                    <span class="text-sm font-medium">تسجيل الرسالة</span>
                </a>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
