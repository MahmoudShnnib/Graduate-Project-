<x-filament-panels::page>
    @php
        $history = $this->getHistory();
        $hasPending = $this->hasPendingRequest();
        $semesters = $this->getSemesters();
        $approvedSemesterIds = $this->getApprovedSemesterIds();
        $selectedIsApproved = $this->isSelectedSemesterAlreadyApproved();
        $student = auth()->user()->student;
        $statusBlocks = $student && ! $student->canPerformActions();
    @endphp

    @if($student)
        @include('filament.components.status-block-banner', ['student' => $student])
    @endif

    {{-- New Request Form --}}
    @if($statusBlocks)
        {{-- The banner above already tells the student they can't submit. --}}
    @elseif($hasPending)
        <x-filament::section>
            <div class="flex items-start gap-3 p-4 rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/30">
                <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.1rem;" class="text-warning-600 dark:text-warning-400" />
                <p class="text-sm text-warning-800 dark:text-warning-300">لديك طلب انقطاع قيد المراجعة. لا يمكن تقديم طلب جديد حتى يتم البت في الطلب الحالي.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-pencil-square style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>تقديم طلب انقطاع</span>
                </div>
            </x-slot>

            <div class="space-y-4 max-w-lg">
                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        الفصل الدراسي <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('semesterId')">
                        <x-filament::input.select wire:model.live="semesterId">
                            <option value="">— اختر الفصل —</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}"
                                    @disabled(in_array($semester->id, $approvedSemesterIds, true))>
                                    {{ $semester->name }}@if(in_array($semester->id, $approvedSemesterIds, true)) (تمت الموافقة مسبقاً)@endif
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('semesterId') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>

                @if($selectedIsApproved)
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/30">
                        <x-heroicon-o-x-circle style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.1rem;" class="text-danger-600 dark:text-danger-400" />
                        <p class="text-sm text-danger-800 dark:text-danger-300">تمت الموافقة مسبقاً على طلب انقطاع لهذا الفصل الدراسي. لا يمكن تقديم طلب جديد لنفس الفصل.</p>
                    </div>
                @endif

                <div>
                    <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white mb-2 block">
                        سبب الانقطاع <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->has('reason')">
                        <textarea wire:model="reason" rows="4"
                            @disabled($selectedIsApproved)
                            class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500 sm:leading-6"
                            placeholder="اكتب سبباً واضحاً ومفصلاً لطلب الانقطاع..."></textarea>
                    </x-filament::input.wrapper>
                    @error('reason') <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="submit" icon="heroicon-o-paper-airplane" :disabled="$selectedIsApproved">
                    إرسال الطلب
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- History --}}
    @if($history->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock style="width: 1.1rem; height: 1.1rem;" class="text-gray-400" />
                    <span>سجل الطلبات</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($history as $request)
                    <div @class([
                        'p-4 rounded-xl border',
                        'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => $request->status->value === 'pending',
                        'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' => $request->status->value === 'approved',
                        'border-danger-200 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10' => $request->status->value === 'rejected',
                    ])>
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $request->semester->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $request->created_at->format('Y/m/d') }}</p>
                            </div>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $request->status->value === 'pending',
                                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $request->status->value === 'approved',
                                'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $request->status->value === 'rejected',
                            ])>{{ $request->status->label() }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->reason }}</p>
                        @if($request->decision_notes)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 pt-2 border-t border-black/10 dark:border-white/10">
                                ملاحظة الإدارة: {{ $request->decision_notes }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
