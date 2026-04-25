@props(['student'])

@php
    /** @var \App\Models\Student $student */
    $statusValue = $student->status?->value;

    [$title, $body] = match ($statusValue) {
        'on_hold' => [
            'قيدك موقوف',
            'حسابك في حالة إيقاف. لا يمكنك تقديم طلبات جديدة. يمكنك الاطلاع على بياناتك ودرجاتك السابقة.',
        ],
        'inactive' => [
            'أنت منقطع عن الدراسة',
            'تم تسجيلك كمنقطع لعدم التسجيل في أي مقررات. لا يمكنك تقديم طلبات جديدة. يمكنك الاطلاع على بياناتك ودرجاتك السابقة.',
        ],
        'transferred' => [
            'تم تسجيل انتقالك',
            'تم تسجيل انتقالك إلى جهة أخرى. لا يمكنك تقديم أي طلبات جديدة.',
        ],
        'graduated' => [
            'تم تخرجك',
            'تم تسجيل تخرجك. يمكنك الاطلاع على بياناتك ودرجاتك، ولكن لا يمكنك تقديم طلبات جديدة.',
        ],
        default => [null, null],
    };
@endphp

@if($title)
    <x-filament::section>
        <div class="flex items-start gap-4 p-4 rounded-xl border bg-danger-50 border-danger-200 dark:bg-danger-500/10 dark:border-danger-500/30">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-500/20">
                <x-heroicon-o-lock-closed style="width: 1.25rem; height: 1.25rem;" class="text-danger-600 dark:text-danger-400" />
            </div>
            <div class="flex-1">
                <p class="font-semibold text-danger-800 dark:text-danger-300 text-sm">{{ $title }}</p>
                <p class="text-sm text-danger-700 dark:text-danger-400 mt-0.5">{{ $body }}</p>
            </div>
        </div>
    </x-filament::section>
@endif
