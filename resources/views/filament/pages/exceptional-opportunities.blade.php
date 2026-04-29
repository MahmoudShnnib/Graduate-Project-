<x-filament-panels::page>
    @php $viewingStudent = $this->getViewingStudent(); @endphp

    @if($viewingStudent)
        {{-- Detail View --}}
        @php
            $latestRequest = $viewingStudent->exceptionalRequests->first();
            $hasPendingOrSummoned = $latestRequest && in_array($latestRequest->status->value, ['pending', 'summoned']);
            $alreadySummoned = $latestRequest && $latestRequest->summon_message;
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
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $viewingStudent->user?->name ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم القيد</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $viewingStudent->enrollment_no ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">التخصص</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $viewingStudent->specialization?->name ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المعدل التراكمي</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format((float) $viewingStudent->gpa, 2) }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">البريد الالكتروني</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ $viewingStudent->user?->email ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم الهاتف</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $viewingStudent->user?->phone ?? '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        {{-- Dismissal Info --}}
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
                    <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">{{ $this->getDismissalReasonText($viewingStudent) }}</p>
                    <p class="text-xs text-danger-600 dark:text-danger-500 mt-1">تاريخ الفصل: {{ $viewingStudent->dismissed_at?->format('Y-m-d') ?? '—' }}</p>

                    @if($viewingStudent->activeWarnings->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-danger-200 dark:border-danger-500/30 space-y-1">
                            <p class="text-xs font-semibold text-danger-700 dark:text-danger-400">التحذيرات النشطة:</p>
                            @foreach($viewingStudent->activeWarnings as $warning)
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

                    {{-- Action Buttons --}}
                    @if($hasPendingOrSummoned)
                        <div class="flex items-center gap-3 pt-2">
                            <button
                                wire:click="acceptRequest({{ $latestRequest->id }})"
                                wire:confirm="هل أنت متأكد من قبول الطلب الاستثنائي؟ سيتم إعادة الطالب إلى الحالة النشطة."
                                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold bg-success-600 text-white hover:bg-success-700 dark:bg-success-500 dark:hover:bg-success-600 transition shadow-sm">
                                <x-heroicon-m-check style="width: 1rem; height: 1rem;" />
                                قبول
                            </button>
                            <button
                                wire:click="rejectRequest({{ $latestRequest->id }})"
                                wire:confirm="هل أنت متأكد من رفض الطلب الاستثنائي؟"
                                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold bg-danger-600 text-white hover:bg-danger-700 dark:bg-danger-500 dark:hover:bg-danger-600 transition shadow-sm">
                                <x-heroicon-m-x-mark style="width: 1rem; height: 1rem;" />
                                رفض
                            </button>
                            @if(! $alreadySummoned)
                                <button
                                    wire:click="openSummonModal({{ $latestRequest->id }})"
                                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold bg-info-600 text-white hover:bg-info-700 dark:bg-info-500 dark:hover:bg-info-600 transition shadow-sm">
                                    <x-heroicon-m-megaphone style="width: 1rem; height: 1rem;" />
                                    استدعاء
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Back Link --}}
        <button wire:click="backToList"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
            <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
            العودة لقائمة الطلاب المفصولين
        </button>

    @else
        {{-- List View --}}
        @php
            $withRequests = $this->getDismissedWithPendingRequest();
            $withoutRequests = $this->getDismissedWithoutRequest();
        @endphp

        @if($withRequests->isEmpty() && $withoutRequests->isEmpty())
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-white/10 mx-auto mb-3">
                        <x-heroicon-o-check-circle style="width: 1.5rem; height: 1.5rem;" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">لا يوجد طلاب مفصولون حالياً.</p>
                </div>
            </x-filament::section>
        @endif

        {{-- Table 1: Students with pending requests --}}
        @if($withRequests->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock style="width: 1.1rem; height: 1.1rem;" class="text-warning-500" />
                        <span>طلبات بانتظار المراجعة ({{ $withRequests->count() }})</span>
                    </div>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الطالب</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">سبب الفصل</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">تاريخ الفصل</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">حالة الطلب</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($withRequests as $student)
                                @php $latestRequest = $this->getLatestRequest($student); @endphp
                                <tr wire:click="viewStudent({{ $student->id }})" class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->enrollment_no ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        <span @class([
                                            'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $student->dismissal_type?->value === 'course_failure',
                                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $student->dismissal_type?->value !== 'course_failure',
                                        ])>{{ $this->getDismissalReasonText($student) }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->dismissed_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        @if($latestRequest)
                                            <span @class([
                                                'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                                'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Pending,
                                                'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400' => $latestRequest->status === \App\Enums\ExceptionalRequestStatus::Summoned,
                                            ])>{{ $latestRequest->status->label() }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Table 2: Dismissed students without pending request --}}
        @if($withoutRequests->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-group style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                        <span>طلاب مفصولون ({{ $withoutRequests->count() }})</span>
                    </div>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">الطالب</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">رقم القيد</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">التخصص</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">سبب الفصل</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">تاريخ الفصل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($withoutRequests as $student)
                                <tr wire:click="viewStudent({{ $student->id }})" class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer">
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $student->user?->name ?? '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->enrollment_no ?? '—' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->specialization?->name ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        <span @class([
                                            'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium',
                                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $student->dismissal_type?->value === 'course_failure',
                                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $student->dismissal_type?->value !== 'course_failure',
                                        ])>{{ $this->getDismissalReasonText($student) }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $student->dismissed_at?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif

    {{-- Summon Modal --}}
    <x-filament::modal id="summon-modal" width="lg">
        <x-slot name="heading">استدعاء الطالب</x-slot>
        <x-slot name="description">اكتب رسالة الاستدعاء التي ستظهر للطالب على لوحة التحكم الخاصة به.</x-slot>

        <div class="space-y-4">
            <textarea
                wire:model="summonMessage"
                rows="4"
                class="w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500"
                placeholder="اكتب رسالة الاستدعاء هنا..."
            ></textarea>
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="sendSummon" color="info" icon="heroicon-m-megaphone">
                إرسال الاستدعاء
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
