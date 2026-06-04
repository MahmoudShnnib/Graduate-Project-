<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'كلية التقنية الإلكترونية - طرابلس' }}</title>

    {{-- Arabic Kufi font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Noto Kufi Arabic"', 'sans-serif'],
                        kufi: ['"Noto Kufi Arabic"', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    {{-- Alpine.js (deferred so the global helpers below are defined first) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: "Noto Kufi Arabic", sans-serif;
            background: #f3f4f6;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen text-slate-900">

<header class="sticky top-0 z-40 bg-white/95 shadow-sm backdrop-blur" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between gap-4 h-20 md:h-24">

            {{-- Brand --}}
            <div class="flex min-w-0 items-center gap-3">
                <div class="min-w-0 text-right">
                    <h1 class="truncate text-sm font-bold leading-tight text-slate-800 sm:text-base lg:text-lg">
                        كلية التقنية الإلكترونية - طرابلس
                    </h1>
                    <p class="hidden truncate text-xs text-slate-500 sm:block">
                        College of Electronic Technology - Tripoli
                    </p>
                </div>

                <div class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-sky-400 text-base font-bold text-sky-500 md:size-12 md:text-xl">
                    CET
                </div>
            </div>

            {{-- Desktop nav --}}
            <nav class="hidden shrink-0 items-center gap-5 text-base font-semibold text-slate-700 xl:flex xl:gap-7">
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">الرئيسية</a>
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">عن الكلية</a>
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">هيئة التدريس</a>
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">الأقسام</a>
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">الأخبار</a>
                <a href="#" class="whitespace-nowrap transition hover:text-blue-700">اتصل بنا</a>
            </nav>

            {{-- Actions: register (desktop) + hamburger (mobile) --}}
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a href="{{ route('registration.create') }}"
                   class="hidden items-center justify-center whitespace-nowrap rounded-xl bg-blue-800 px-5 py-2.5 text-base font-bold text-white shadow transition hover:bg-blue-900 sm:inline-flex md:px-7 md:py-3">
                    التسجيل
                </a>

                {{-- Hamburger / close --}}
                <button type="button"
                        @click="mobileOpen = ! mobileOpen"
                        :aria-expanded="mobileOpen"
                        aria-label="القائمة"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-700 transition hover:bg-slate-100 xl:hidden">
                    <svg x-show="! mobileOpen" class="size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <nav x-show="mobileOpen"
             x-cloak
             @click.outside="mobileOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-slate-100 py-3 lg:hidden">
            <div class="flex flex-col gap-1 text-base font-semibold text-slate-700">
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">الرئيسية</a>
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">عن الكلية</a>
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">هيئة التدريس</a>
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">الأقسام</a>
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">الأخبار</a>
                <a href="#" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">اتصل بنا</a>
                <a href="{{ route('registration.create') }}"
                   class="mt-2 rounded-xl bg-blue-800 px-3 py-3 text-center text-white shadow transition hover:bg-blue-900 sm:hidden">
                    التسجيل
                </a>
            </div>
        </nav>
    </div>
</header>

<section class="relative overflow-hidden bg-gradient-to-bl from-blue-900 via-blue-800 to-sky-700">
    {{-- soft decorative glows --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-24 -start-24 h-96 w-96 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -end-16 h-[28rem] w-[28rem] rounded-full bg-blue-500/20 blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-6 py-20 lg:py-28">
        <div class="grid items-center gap-12 lg:grid-cols-2">

            {{-- Copy (right side in RTL) --}}
            <div class="text-right text-white">
                <span class="inline-flex rounded-full bg-white/15 px-5 py-2 text-sm font-medium backdrop-blur">
                    المؤسسة التعليمية الرائدة في مجال التقنية
                </span>

                <h2 class="mt-6 text-4xl lg:text-6xl font-extrabold leading-tight">
                    كلية التقنية الإلكترونية
                    <br>
                    طرابلس
                </h2>

                <p class="mt-6 text-xl lg:text-2xl text-sky-100">
                    نحو مستقبل رقمي أفضل
                </p>

                <div class="mt-10">
                    <a href="#registration-form"
                       class="inline-flex items-center justify-center rounded-full bg-white px-8 py-4 text-lg font-bold text-blue-800 shadow-lg shadow-blue-950/30 transition hover:bg-sky-50 hover:shadow-xl">
                        التقديم على الدراسات العليا
                    </a>
                </div>
            </div>

            {{-- Illustration (left side in RTL) --}}
            <div class="relative">
                <svg viewBox="0 0 560 460" class="mx-auto w-full max-w-md drop-shadow-2xl" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="رسم توضيحي للدراسات العليا">
                    {{-- backdrop discs --}}
                    <circle cx="280" cy="235" r="215" fill="#ffffff" opacity="0.06"/>
                    <circle cx="280" cy="235" r="165" fill="#ffffff" opacity="0.05"/>

                    {{-- floating accents --}}
                    <circle cx="105" cy="120" r="10" fill="#bae6fd" opacity="0.7"/>
                    <circle cx="455" cy="300" r="15" fill="#38bdf8" opacity="0.45"/>
                    <circle cx="92" cy="330" r="7" fill="#7dd3fc" opacity="0.8"/>
                    <g stroke="#bae6fd" stroke-width="4" stroke-linecap="round" opacity="0.8">
                        <path d="M428,86 v26"/>
                        <path d="M415,99 h26"/>
                    </g>

                    {{-- graduation cap --}}
                    <path d="M236,150 L236,184 C236,184 254,206 280,206 C306,206 324,184 324,184 L324,150 Z" fill="#1e3a8a"/>
                    <path d="M236,150 L236,168 C236,168 254,184 280,184 C306,184 324,168 324,168 L324,150 Z" fill="#1e40af"/>
                    <polygon points="280,96 436,156 280,156 124,156" fill="#3b82f6"/>
                    <polygon points="124,156 436,156 280,216 124,156" fill="#1d4ed8"/>
                    <circle cx="280" cy="156" r="10" fill="#fbbf24"/>
                    <path d="M280,156 H392" stroke="#fbbf24" stroke-width="4" fill="none"/>
                    <path d="M392,156 C397,184 397,200 392,214" stroke="#fbbf24" stroke-width="4" fill="none"/>
                    <circle cx="392" cy="218" r="8" fill="#f59e0b"/>
                    <g stroke="#fbbf24" stroke-width="3" stroke-linecap="round">
                        <path d="M386,224 l-3,22"/>
                        <path d="M392,225 v23"/>
                        <path d="M398,224 l3,22"/>
                    </g>

                    {{-- stacked books --}}
                    <rect x="150" y="356" width="252" height="34" rx="9" fill="#1d4ed8"/>
                    <rect x="160" y="356" width="16" height="34" fill="#1e3a8a"/>
                    <rect x="136" y="320" width="252" height="34" rx="9" fill="#0ea5e9"/>
                    <rect x="146" y="320" width="16" height="34" fill="#0284c7"/>
                    <rect x="158" y="284" width="252" height="34" rx="9" fill="#60a5fa"/>
                    <rect x="168" y="284" width="16" height="34" fill="#3b82f6"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<main id="registration-form" class="max-w-7xl mx-auto px-6 py-16">
    {{ $slot }}
</main>

</body>
</html>
