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
        // Brand palette — Azure (primary) + Teal (secondary).
        // Anchored on the supplied hexes (azure 600/400/700, teal 500/400/700);
        // remapping blue + sky → azure and teal → teal rebrands the whole site
        // without touching individual utility classes.
        const azure = {
            50:  '#ECF4FB', 100: '#D6E8F5', 200: '#B0D2EC', 300: '#74AEDD',
            400: '#3A93D6', 500: '#2682C6', 600: '#1774B6', 700: '#125E93',
            800: '#0E4D79', 900: '#0C3E61', 950: '#082942',
        };
        const teal = {
            50:  '#E7FAF7', 100: '#C8F3ED', 200: '#94E7DC', 300: '#5AD6C8',
            400: '#2BC7B6', 500: '#16B0A0', 600: '#119488', 700: '#0E7D72',
            800: '#0B6359', 900: '#094E47', 950: '#042E2A',
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: azure,
                        sky: azure,
                        teal: teal,
                        azure: azure,
                        brand: azure,
                    },
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
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 7rem; /* offset for the sticky header on anchor jumps */
        }

        body {
            font-family: "Noto Kufi Arabic", sans-serif;
            background: #f3f4f6;
        }

        [x-cloak] { display: none !important; }

        /* Scroll-reveal (enabled only when JS runs, so no-JS keeps content visible) */
        .reveal-enabled .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s ease, transform .7s ease;
        }
        .reveal-enabled .reveal.is-visible {
            opacity: 1;
            transform: none;
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .reveal-enabled .reveal { opacity: 1; transform: none; transition: none; }
        }
    </style>
</head>
<body class="min-h-screen text-slate-900">
<script>document.documentElement.classList.add('reveal-enabled');</script>

@php
    // Public nav — anchors into the landing sections on the /apply page.
    // Absolute (route + #hash) so they also work from /apply/track.
    // 'id' matches the on-page section id used by the scroll-spy.
    $applyUrl = route('registration.create');
    $navLinks = [
        ['label' => 'عن الكلية', 'href' => $applyUrl.'#about', 'id' => 'about'],
        ['label' => 'الأقسام', 'href' => $applyUrl.'#departments', 'id' => 'departments'],
        ['label' => 'الأخبار', 'href' => $applyUrl.'#news', 'id' => 'news'],
        ['label' => 'اتصل بنا', 'href' => $applyUrl.'#contact', 'id' => 'contact'],
    ];
@endphp

{{-- Reading-progress bar — fixed at the very top so it stays visible even when the header hides on scroll --}}
<div class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1 bg-transparent" x-data>
    <div class="h-full bg-gradient-to-l from-sky-400 to-blue-700 transition-[width] duration-150 ease-out"
         :style="`width: ${$store.ui?.progress ?? 0}%`"></div>
</div>

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur transition-[transform,box-shadow] duration-300 will-change-transform"
        x-data="{ mobileOpen: false }"
        :class="{
            'shadow-lg': $store.ui?.scrolled,
            'shadow-sm': !$store.ui?.scrolled,
            '-translate-y-full': $store.ui?.hideNav && !mobileOpen,
        }">

    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between gap-4 h-24 md:h-28">

            {{-- Brand --}}
            <div class="flex min-w-0 shrink items-center">
                <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}"
                     alt="كلية التقنية الإلكترونية - طرابلس"
                     class="h-12 w-auto max-w-full object-contain md:h-16">
            </div>

            {{-- Desktop nav --}}
            <nav class="hidden shrink-0 items-center gap-5 text-base font-semibold text-slate-700 lg:flex lg:gap-8">
                @foreach($navLinks as $link)
                    <a href="{{ $link['href'] }}"
                       :class="$store.ui?.active === '{{ $link['id'] }}' ? 'text-blue-700' : 'text-slate-700'"
                       class="relative whitespace-nowrap pb-1 transition hover:text-blue-700">
                        {{ $link['label'] }}
                        <span class="absolute inset-x-0 -bottom-0.5 h-0.5 origin-center rounded-full bg-blue-700 transition-transform duration-300"
                              :class="$store.ui?.active === '{{ $link['id'] }}' ? 'scale-x-100' : 'scale-x-0'"></span>
                    </a>
                @endforeach
            </nav>

            {{-- Actions: register (desktop) + hamburger (mobile) --}}
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a href="{{ route('registration.create') }}#apply"
                   class="hidden items-center justify-center whitespace-nowrap rounded-xl bg-blue-800 px-5 py-2.5 text-base font-bold text-white shadow transition hover:bg-blue-900 sm:inline-flex md:px-7 md:py-3">
                    التسجيل
                </a>

                {{-- Hamburger / close --}}
                <button type="button"
                        @click="mobileOpen = ! mobileOpen"
                        :aria-expanded="mobileOpen"
                        aria-label="القائمة"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-700 transition hover:bg-slate-100 lg:hidden">
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
                @foreach($navLinks as $link)
                    <a href="{{ $link['href'] }}" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('registration.create') }}#apply" @click="mobileOpen = false"
                   class="mt-2 rounded-xl bg-blue-800 px-3 py-3 text-center text-white shadow transition hover:bg-blue-900 sm:hidden">
                    التسجيل
                </a>
            </div>
        </nav>
    </div>
</header>

<section id="home" data-spy class="relative overflow-hidden bg-slate-900">

    {{-- ===== Desktop (lg+): full-bleed photo with the copy over the right side ===== --}}
    <div class="relative hidden lg:block">
        <img src="{{ asset('images/hero-apply.jpg') }}?v={{ @filemtime(public_path('images/hero-apply.jpg')) }}" alt="" aria-hidden="true"
             class="absolute inset-0 h-full w-full object-cover object-center">
        {{-- neutral darkening for depth (no blue tint) --}}
        <div class="pointer-events-none absolute inset-0 bg-black/30"></div>
        {{-- directional wash — photo clearer on the left, darker on the right where the text sits --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-black/75 via-black/40 to-transparent"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-28">
            <div class="grid min-h-[26rem] grid-cols-2 items-center">
                <div class="text-right">
                    <h2 class="text-6xl font-extrabold leading-tight text-white [text-shadow:0_2px_16px_rgba(0,0,0,0.55)]">
                        كلية التقنية الإلكترونية
                        <br>
                        طرابلس
                    </h2>
                    <p class="mt-6 text-2xl font-medium text-white/85 [text-shadow:0_1px_10px_rgba(0,0,0,0.5)]">نحو مستقبل رقمي أفضل</p>
                    <div class="mt-10">
                        <a href="#apply"
                           class="inline-flex items-center justify-center rounded-full bg-blue-800 px-8 py-4 text-lg font-bold text-white shadow-lg shadow-black/30 transition hover:bg-blue-900 hover:shadow-xl">
                            التقديم على الدراسات العليا
                        </a>
                    </div>
                </div>
                <div aria-hidden="true"></div>
            </div>
        </div>
    </div>

    {{-- ===== Mobile (<lg): stacked — photo banner on top, copy on white below (no text over the photo) ===== --}}
    <div class="bg-white lg:hidden">
        {{-- photo banner --}}
        <div class="relative">
            <img src="{{ asset('images/hero-apply.jpg') }}?v={{ @filemtime(public_path('images/hero-apply.jpg')) }}" alt="مبنى كلية التقنية الإلكترونية - طرابلس"
                 class="h-64 w-full object-cover object-center sm:h-72">
            {{-- soft fade into the white copy area below --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        {{-- copy --}}
        <div class="px-6 pb-12 pt-7 text-center">
            <h2 class="text-3xl font-extrabold leading-tight text-blue-900 sm:text-4xl">
                كلية التقنية الإلكترونية طرابلس
            </h2>
            <p class="mt-3 text-lg font-semibold text-slate-500">نحو مستقبل رقمي أفضل</p>
            <div class="mt-7">
                <a href="#apply"
                   class="inline-flex items-center justify-center rounded-full bg-blue-800 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-blue-900 hover:shadow-xl">
                    التقديم على الدراسات العليا
                </a>
            </div>
        </div>
    </div>
</section>

<main id="registration-form" class="max-w-7xl mx-auto px-6 py-16">
    {{ $slot }}
</main>

{{-- ============================ Footer ============================ --}}
<footer class="bg-blue-950 text-center text-slate-300">
    <div class="mx-auto max-w-7xl px-6 py-12">
        <p class="text-sm text-slate-400">© جميع الحقوق محفوظة لكلية التقنية الإلكترونية {{ date('Y') }}</p>

        <p class="mt-7 text-base font-bold text-slate-100">فريق التطوير</p>

        @php
            $devs = [
                ['name' => 'محمود شنيب', 'url' => 'https://github.com/MahmoudShnnib'],
                ['name' => 'محمد الزايدي', 'url' => 'https://github.com/mohamedzaydi782-alt'],
                ['name' => 'عمر شنيب', 'url' => 'https://github.com/Omar-shennib'],
            ];
        @endphp
        <div class="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-3 text-sm font-semibold">
            @foreach($devs as $dev)
                <a href="{{ $dev['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 text-slate-200 transition hover:text-sky-400">
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56 0-.27-.01-1.16-.02-2.1-3.2.7-3.88-1.37-3.88-1.37-.52-1.33-1.28-1.69-1.28-1.69-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.23-1.28-5.23-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.84 1.19 3.1 0 4.43-2.69 5.41-5.25 5.69.41.36.78 1.06.78 2.14 0 1.55-.01 2.8-.01 3.18 0 .31.21.68.8.56A10.52 10.52 0 0023.5 12C23.5 5.73 18.27.5 12 .5z"/>
                    </svg>
                    <span>{{ $dev['name'] }}</span>
                </a>
                @unless($loop->last)
                    <span class="text-slate-600" aria-hidden="true">•</span>
                @endunless
            @endforeach
        </div>

        <div class="mt-8">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/5 px-5 py-2 text-sm text-slate-200 ring-1 ring-white/10">
                <span class="font-bold text-slate-100">اشراف:</span>
                أ. مصطفى قاباج
            </span>
        </div>
    </div>
</footer>

{{-- Toast notifications --}}
<div x-data
     x-show="$store.toast?.show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-5 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-5"
     class="fixed inset-x-0 top-6 z-[60] flex justify-center px-4"
     role="alert" aria-live="assertive">
    <div class="pointer-events-auto flex w-full max-w-md items-start gap-3 overflow-hidden rounded-2xl bg-white p-4 shadow-2xl ring-1"
         :class="$store.toast?.type === 'error' ? 'ring-red-200' : 'ring-emerald-200'">
        {{-- icon --}}
        <div class="flex size-11 shrink-0 items-center justify-center rounded-full"
             :class="$store.toast?.type === 'error' ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'">
            <svg x-show="$store.toast?.type === 'error'" class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <svg x-show="$store.toast?.type !== 'error'" x-cloak class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        {{-- text --}}
        <div class="flex-1 pt-0.5">
            <p class="text-sm font-bold text-slate-800" x-text="$store.toast?.title"></p>
            <p class="mt-1 text-sm leading-relaxed text-slate-500" x-text="$store.toast?.message"></p>
        </div>
        {{-- close --}}
        <button type="button" @click="$store.toast.dismiss()" aria-label="إغلاق"
                class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

{{-- Back-to-top --}}
<button type="button"
        x-data
        x-show="$store.ui?.showTop"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        aria-label="العودة للأعلى"
        class="fixed bottom-6 start-6 z-50 flex size-12 items-center justify-center rounded-full bg-white text-blue-800 ring-1 ring-blue-100 shadow-lg shadow-blue-900/20 transition hover:bg-blue-50">
    <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
    </svg>
</button>

<script>
    // Shared UI store: scroll progress, sticky-header shadow, back-to-top
    // visibility, scroll-spy active section, and scroll-reveal — all driven by
    // a single scroll listener + IntersectionObservers (no Alpine plugins).
    document.addEventListener('alpine:init', () => {
        Alpine.store('ui', {
            scrolled: false,
            showTop: false,
            progress: 0,
            active: 'home',
            hideNav: false,

            init() {
                let lastY = window.scrollY || document.documentElement.scrollTop;
                const onScroll = () => {
                    const y = window.scrollY || document.documentElement.scrollTop;
                    const doc = document.documentElement;
                    const max = doc.scrollHeight - doc.clientHeight;
                    this.scrolled = y > 8;
                    this.showTop = y > 600;
                    this.progress = max > 0 ? Math.min(100, (y / max) * 100) : 0;

                    // Hide the header when scrolling down (past its height), reveal on scroll up.
                    if (y <= 120) {
                        this.hideNav = false;
                    } else if (y > lastY + 6) {
                        this.hideNav = true;
                    } else if (y < lastY - 6) {
                        this.hideNav = false;
                    }
                    lastY = y;
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', onScroll, { passive: true });
                onScroll();

                // Scroll-spy: mark the section crossing the viewport middle.
                const spied = document.querySelectorAll('[data-spy]');
                if ('IntersectionObserver' in window && spied.length) {
                    const spy = new IntersectionObserver((entries) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting && e.target.id) {
                                this.active = e.target.id;
                            }
                        });
                    }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
                    spied.forEach((el) => spy.observe(el));
                }

                // Scroll-reveal: fade/slide sections in as they appear.
                const reveals = document.querySelectorAll('.reveal');
                if ('IntersectionObserver' in window && reveals.length) {
                    const ro = new IntersectionObserver((entries, obs) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add('is-visible');
                                obs.unobserve(e.target);
                            }
                        });
                    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
                    reveals.forEach((el) => ro.observe(el));
                    // Safety: reveal everything after a moment even if an observer misses.
                    setTimeout(() => reveals.forEach((el) => el.classList.add('is-visible')), 2500);
                } else {
                    reveals.forEach((el) => el.classList.add('is-visible'));
                }
            },
        });

        Alpine.store('ui').init();

        // Toast notifications (replaces native alert()).
        Alpine.store('toast', {
            show: false,
            title: '',
            message: '',
            type: 'error',
            timer: null,
            notify(title, message, type = 'error') {
                this.title = title;
                this.message = message;
                this.type = type;
                this.show = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => { this.show = false; }, 6000);
            },
            dismiss() {
                this.show = false;
                clearTimeout(this.timer);
            },
        });
    });
</script>

</body>
</html>
