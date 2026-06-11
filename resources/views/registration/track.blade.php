<x-layout.app title="متابعة طلب القبول" :hide-hero="true">
    <div class="mx-auto max-w-2xl">

        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                متابعة الطلب
            </span>
            <h2 class="mt-4 text-3xl font-extrabold text-slate-900">
                متابعة حالة طلب القبول
            </h2>
            @unless(isset($application))
                <p class="mt-3 text-base text-slate-500">أدخل رقم التتبع والرقم الوطني للاستعلام عن حالة طلبك</p>
            @endunless
        </div>

        {{-- Search Form — hidden once a result is shown; the visitor just needs their status --}}
        @unless(isset($application))
        <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
            <form method="POST" action="{{ route('application.track') }}" class="space-y-6">
                @csrf

                <div>
                    <x-form.label for="tracking_code">رقم التتبع <span class="text-red-500">*</span></x-form.label>
                    <x-form.input id="tracking_code" name="tracking_code" type="text" value="{{ old('tracking_code') }}" placeholder="مثال: APP-2026-0001" required dir="ltr" class="text-center tracking-widest" />
                    <x-form.error name="tracking_code" />
                </div>

                <div>
                    <x-form.label for="national_id">الرقم الوطني <span class="text-red-500">*</span></x-form.label>
                    <x-form.input id="national_id" name="national_id" type="text" value="{{ old('national_id') }}" placeholder="الرقم الوطني المكون من 12 رقماً" required dir="ltr" class="text-center tracking-widest" />
                    <x-form.error name="national_id" />
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 px-6 py-3 text-center font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    استعلام
                </button>
            </form>
        </div>
        @endunless

        {{-- Result --}}
        @if(isset($application))
            <div class="mt-8 space-y-6">

                {{-- Status Banner --}}
                <div @class([
                    'rounded-2xl border p-6 flex items-center gap-4',
                    'bg-amber-50 border-amber-200' => $application->status->value === 'pending',
                    'bg-blue-50 border-blue-200' => $application->status->value === 'initially_approved',
                    'bg-green-50 border-green-200' => $application->status->value === 'accepted',
                    'bg-red-50 border-red-200' => $application->status->value === 'rejected',
                ])>
                    <div @class([
                        'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                        'bg-amber-100' => $application->status->value === 'pending',
                        'bg-blue-100' => $application->status->value === 'initially_approved',
                        'bg-green-100' => $application->status->value === 'accepted',
                        'bg-red-100' => $application->status->value === 'rejected',
                    ])>
                        @if($application->status->value === 'accepted')
                            <svg class="size-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($application->status->value === 'rejected')
                            <svg class="size-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($application->status->value === 'initially_approved')
                            <svg class="size-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @else
                            <svg class="size-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-900">
                            {{ $application->status->label() }}
                        </p>
                        <p class="text-sm text-slate-500">{{ $application->name }}</p>
                        @if($application->status->value === 'initially_approved' && $application->exam_date)
                            <p class="mt-1 text-sm font-medium text-blue-700">
                                موعد الاختبار: {{ $application->exam_date->format('Y/m/d H:i') }}
                            </p>
                        @endif
                        @if($application->status->value === 'rejected' && $application->rejection_reason)
                            <p class="mt-1 text-sm text-red-700">{{ $application->rejection_reason }}</p>
                        @endif
                    </div>
                </div>

                {{-- 3-step status tracker (read-only) --}}
                @php
                    $st = $application->status->value;
                    $isAccepted = $st === 'accepted';
                    $isRejected = $st === 'rejected';
                    $reviewed = in_array($st, ['initially_approved', 'accepted', 'rejected'], true);
                    $decided = $isAccepted || $isRejected;

                    $trackSteps = [
                        [
                            'title' => 'تقديم الطلب',
                            'caption' => 'تم الاستلام',
                            'state' => 'done',
                        ],
                        [
                            'title' => 'مراجعة الطلب',
                            'caption' => $reviewed ? 'تمت المراجعة' : 'قيد المراجعة',
                            'state' => $reviewed ? 'done' : 'current',
                        ],
                        [
                            'title' => 'القرار النهائي',
                            'caption' => $isAccepted ? 'مقبول' : ($isRejected ? 'مرفوض' : 'بانتظار القرار'),
                            'state' => $isAccepted ? 'done' : ($isRejected ? 'rejected' : 'pending'),
                        ],
                    ];
                @endphp
                <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                    <h3 class="mb-8 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">حالة الطلب</h3>

                    <div class="relative px-2">
                        {{-- background track line (passes through the circle centers) --}}
                        <div class="absolute top-6 right-10 left-10 h-1 rounded-full bg-slate-200 sm:right-14 sm:left-14"></div>
                        {{-- progress fill — from the start (right, RTL) up to the reached node --}}
                        <div @class([
                            'absolute top-6 right-10 h-1 rounded-full bg-emerald-400 sm:right-14',
                            'left-10 sm:left-14' => $decided,
                            'left-1/2' => ! $decided,
                        ])></div>

                        <div class="relative flex justify-between">
                            @foreach($trackSteps as $i => $step)
                                <div class="flex w-20 flex-col items-center text-center sm:w-28">
                                    <span @class([
                                        'relative z-10 flex size-12 items-center justify-center rounded-full ring-4 ring-white',
                                        'bg-emerald-100 text-emerald-600' => $step['state'] === 'done',
                                        'bg-amber-100 text-amber-600' => $step['state'] === 'current',
                                        'bg-red-100 text-red-600' => $step['state'] === 'rejected',
                                        'bg-slate-100 text-slate-400' => $step['state'] === 'pending',
                                    ])>
                                        @switch($step['state'])
                                            @case('done')
                                                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                @break
                                            @case('current')
                                                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @break
                                            @case('rejected')
                                                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                @break
                                            @default
                                                <span class="text-base font-bold">{{ $i + 1 }}</span>
                                        @endswitch
                                    </span>
                                    <h4 @class([
                                        'mt-3 text-sm font-bold',
                                        'text-slate-900' => $step['state'] === 'done',
                                        'text-amber-700' => $step['state'] === 'current',
                                        'text-red-700' => $step['state'] === 'rejected',
                                        'text-slate-400' => $step['state'] === 'pending',
                                    ])>{{ $step['title'] }}</h4>
                                    <p @class([
                                        'mt-0.5 text-xs',
                                        'text-emerald-600' => $step['state'] === 'done',
                                        'text-amber-600' => $step['state'] === 'current',
                                        'text-red-600' => $step['state'] === 'rejected',
                                        'text-slate-400' => $step['state'] === 'pending',
                                    ])>{{ $step['caption'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                    <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">مسار الطلب</h3>
                    <ol class="relative border-r-2 border-slate-200 me-3 space-y-6">
                        {{-- Submitted --}}
                        <li class="ms-6">
                            <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 ring-4 ring-white">
                                <svg class="size-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </span>
                            <h4 class="text-sm font-semibold text-slate-900">تقديم الطلب</h4>
                            <p class="text-xs text-slate-400">{{ $application->created_at->diffForHumans() }}</p>
                        </li>

                        {{-- Reviewed --}}
                        @if(in_array($application->status->value, ['initially_approved', 'accepted', 'rejected']))
                            <li class="ms-6">
                                <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 ring-4 ring-white">
                                    <svg class="size-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                </span>
                                <h4 class="text-sm font-semibold text-slate-900">مراجعة الطلب</h4>
                            </li>
                        @endif

                        {{-- Exam scheduled --}}
                        @if(in_array($application->status->value, ['initially_approved', 'accepted']) && $application->exam_date)
                            <li class="ms-6">
                                <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 ring-4 ring-white">
                                    <svg class="size-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                </span>
                                <h4 class="text-sm font-semibold text-slate-900">مقبول مبدئياً — بانتظار الامتحان</h4>
                                <p class="text-xs text-slate-400">{{ $application->exam_date->format('Y/m/d H:i') }}</p>
                            </li>
                        @endif

                        {{-- Accepted --}}
                        @if($application->status->value === 'accepted')
                            <li class="ms-6">
                                <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-green-100 ring-4 ring-white">
                                    <svg class="size-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                                <h4 class="text-sm font-semibold text-green-700">تم قبول الطلب</h4>
                            </li>
                        @endif

                        {{-- Rejected --}}
                        @if($application->status->value === 'rejected')
                            <li class="ms-6">
                                <span class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full bg-red-100 ring-4 ring-white">
                                    <svg class="size-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </span>
                                <h4 class="text-sm font-semibold text-red-700">تم رفض الطلب</h4>
                            </li>
                        @endif
                    </ol>
                </div>

                @if($application->notes)
                    <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                        <h3 class="mb-4 text-lg font-bold text-slate-800">ملاحظات</h3>
                        <p class="text-sm text-slate-600">{{ $application->notes }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Links --}}
        <div class="mt-8 text-center">
            <a href="{{ route('registration.create') }}" class="text-sm font-medium text-blue-600 underline hover:text-blue-800">
                تقديم طلب جديد
            </a>
        </div>
    </div>
</x-layout.app>
