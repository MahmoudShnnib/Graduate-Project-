<x-filament-widgets::widget>
    @php
        $header = $this->getHeaderData();
    @endphp

    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-l from-blue-900 via-blue-800 to-sky-700 p-6 shadow-sm sm:p-7">
        {{-- soft decorative glow, matching the /apply hero --}}
        <div class="pointer-events-none absolute -left-10 -top-16 size-48 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 right-10 size-56 rounded-full bg-blue-400/10 blur-3xl"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            {{-- Greeting --}}
            <div class="flex items-center gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                    @svg('heroicon-o-hand-raised', 'size-7 text-white')
                </span>
                <div class="min-w-0">
                    <p class="text-sm text-sky-100/80">مرحباً</p>
                    <h2 class="truncate text-xl font-extrabold text-white sm:text-2xl">{{ $header['name'] }}</h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        @if ($header['roleLabel'])
                            <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-white ring-1 ring-white/20">
                                {{ $header['roleLabel'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1 text-xs text-sky-100/90">
                            @svg('heroicon-o-calendar', 'size-4')
                            {{ $header['semesterName'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                @if ($header['canEditSettings'])
                    {{-- Display-only status (the actual toggle lives in the settings modal) --}}
                    <div title="متطلب رمز التحقق (OTP) على صفحة التقديم — يُضبط من إعدادات النظام"
                         @class([
                             'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white ring-1',
                             'bg-white/10 ring-white/20' => $header['applyOtpEnabled'],
                             'bg-amber-500/30 ring-amber-300/50' => ! $header['applyOtpEnabled'],
                         ])>
                        @svg($header['applyOtpEnabled'] ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation', 'size-5')
                        <span class="hidden sm:inline">رمز التحقق: {{ $header['applyOtpEnabled'] ? 'مُفعّل' : 'مُعطّل' }}</span>
                    </div>

                    <button type="button" wire:click="mountAction('editSettings')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                        @svg('heroicon-o-cog-6-tooth', 'size-5')
                        <span class="hidden sm:inline">إعدادات النظام</span>
                    </button>
                @endif

                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-blue-900 shadow-sm transition hover:bg-sky-50">
                        @svg('heroicon-o-arrow-right-on-rectangle', 'size-5')
                        <span class="hidden sm:inline">تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>

        <x-filament-actions::modals />
    </div>
</x-filament-widgets::widget>
