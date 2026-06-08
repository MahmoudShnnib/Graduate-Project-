<x-layout.app title="طلب الالتحاق بالدراسات العليا">

    @php
        $settings = \App\Models\Setting::allKeyed();
    @endphp

    {{-- ===== Magic FX (scoped to /apply — Play CDN, no build step) ===== --}}
    <style>
        @keyframes mgShimmer { to { background-position: 200% center; } }
        @keyframes mgDrawX { to { transform: scaleX(1); } }

        .mg-card { position:relative; transition:transform .2s ease, box-shadow .2s ease; }
        .mg-card:hover { transform:translateY(-4px); box-shadow:0 18px 36px -16px rgba(30,58,138,.30); }

        .mg-gtext { color:#0f172a; }

        .mg-line { transform:scaleX(0); transform-origin:right; }
        .reveal-enabled .is-visible .mg-line { animation:mgDrawX 1.2s ease .15s forwards; }

        /* Mobile timeline nodes: hidden until JS reveals them in order on scroll */
        .tl-node { opacity:0; transform:scale(.4); transition:opacity .45s ease, transform .5s cubic-bezier(.34,1.56,.64,1); }
        .tl-node.is-on { opacity:1; transform:scale(1); }

        @media (prefers-reduced-motion: reduce) {
            .mg-gtext { animation:none !important; }
            .mg-card { transition:none !important; }
            .reveal-enabled .is-visible .mg-line { animation:none; transform:scaleX(1); }
            .tl-node { opacity:1 !important; transform:none !important; transition:none !important; }
            [data-tl-fill] { transition:none !important; }
        }
    </style>

    {{-- ============================ Landing sections ============================ --}}
    <div class="space-y-20 mb-20">

        {{-- عن الكلية --}}
        <section id="about" data-spy class="reveal scroll-mt-28">
            <div class="grid items-center gap-10 lg:grid-cols-2">

                {{-- Text + checklist (right in RTL) --}}
                <div>
                    <span class="inline-flex rounded-full bg-blue-100/80 px-4 py-2 text-sm font-bold text-blue-700 ring-1 ring-blue-200">عن الكلية</span>
                    <h2 class="mt-4 text-3xl font-extrabold leading-tight text-slate-900 md:text-4xl">
                        بوابتك إلى الدراسات العليا
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-500">
                        من خلال هذه المنصة يمكنك التقديم على برامج الإجازة العليا (الماجستير) في {{ $settings['college_name'] }}،
                        ومتابعة مسار طلبك بالكامل إلكترونياً — من تعبئة الاستمارة ورفع المستندات، إلى معرفة موعد الاختبار وحالة القبول.
                    </p>

                    <ul class="mt-7 space-y-4">
                        @foreach([
                            'برامج ماجستير في تخصصات تقنية معتمدة',
                            'تقديم وتسجيل إلكتروني بالكامل دون حضور',
                            'متابعة فورية لحالة الطلب برقم التتبع',
                            'إشعارات عبر الواتساب',
                        ] as $point)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </span>
                                <span class="text-base font-medium text-slate-700">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="#apply" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-blue-800 px-6 py-3 text-base font-bold text-white shadow transition hover:bg-blue-900">
                        ابدأ التقديم الآن
                        <svg class="size-5 -scale-x-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>

                {{-- Visual (left in RTL) --}}
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-bl from-blue-800 to-sky-600 p-12 text-center shadow-xl">
                    <div class="pointer-events-none absolute -top-12 -start-10 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-14 -end-8 h-52 w-52 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative">
                        <div class="mx-auto mb-6 flex size-24 items-center justify-center rounded-3xl bg-white/15 backdrop-blur">
                            <svg class="size-14 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                        </div>
                        <p class="text-2xl font-extrabold text-white">برامج الإجازة العليا</p>
                        <p class="mt-2 text-sky-100">الماجستير في كلية التقنية</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- الأقسام --}}
        <section id="departments" data-spy class="reveal scroll-mt-28">
            <div class="relative z-10">
                <div class="mb-10 text-center">
                    <span class="inline-flex rounded-full bg-blue-100/80 px-4 py-2 text-sm font-bold text-blue-700 ring-1 ring-blue-200">الأقسام</span>
                    <h2 class="mt-4 text-3xl font-extrabold md:text-4xl"><span class="mg-gtext">الأقسام والتخصصات</span></h2>
                    <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">
                        تطرح الكلية برامج الدراسات العليا (الماجستير) في التخصصات التالية — اختر مسارك وابدأ رحلتك:
                    </p>
                </div>

                @if($specializations->isNotEmpty())
                    @php
                        $accents = [
                            ['ic' => 'bg-blue-100 text-blue-700 group-hover:bg-blue-600 group-hover:text-white', 'bar' => 'from-blue-500 to-sky-400'],
                            ['ic' => 'bg-teal-100 text-teal-700 group-hover:bg-teal-600 group-hover:text-white', 'bar' => 'from-teal-500 to-teal-300'],
                            ['ic' => 'bg-cyan-100 text-cyan-700 group-hover:bg-cyan-600 group-hover:text-white', 'bar' => 'from-cyan-500 to-sky-400'],
                            ['ic' => 'bg-blue-100 text-blue-700 group-hover:bg-blue-600 group-hover:text-white', 'bar' => 'from-blue-600 to-sky-400'],
                            ['ic' => 'bg-teal-100 text-teal-700 group-hover:bg-teal-600 group-hover:text-white', 'bar' => 'from-teal-500 to-emerald-400'],
                            ['ic' => 'bg-sky-100 text-sky-700 group-hover:bg-sky-600 group-hover:text-white', 'bar' => 'from-sky-500 to-blue-400'],
                        ];
                        // Per-specialization icons (white PNG glyphs on the colored tile). Mapped by code, with a name fallback.
                        $specIconsByCode = [1 => 'comm.png', 2 => 'computer.png', 3 => 'control.png'];
                        $specIconsByName = ['الإتصالات' => 'comm.png', 'الحاسب الآلي' => 'computer.png', 'التحكم الآلي' => 'control.png'];
                    @endphp
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($specializations as $specialization)
                            @php
                                $a = $accents[$loop->index % count($accents)];
                                $icon = $specIconsByCode[$specialization->code] ?? ($specIconsByName[$specialization->name] ?? null);
                            @endphp
                            <div                                 class="group mg-card bg-white relative overflow-hidden rounded-2xl p-6 ring-1 ring-slate-200 shadow-md">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l {{ $a['bar'] }} opacity-0 transition group-hover:opacity-100"></div>
                                <div class="relative z-10">
                                    <div class="mb-4 flex items-center">
                                        <div class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-bl {{ $a['bar'] }} shadow-sm">
                                            @if($icon)
                                                <img src="{{ asset('images/specializations/'.$icon) }}?v={{ @filemtime(public_path('images/specializations/'.$icon)) }}" alt="" class="size-7 object-contain">
                                            @else
                                                <svg class="size-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                                            @endif
                                        </div>
                                    </div>
                                    <h3 class="mb-3 text-lg font-bold text-slate-800">{{ $specialization->name }}</h3>
                                    <a href="#apply" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700 transition group-hover:gap-2 hover:text-blue-900">
                                        قدّم على هذا التخصص
                                        <svg class="size-4 -scale-x-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-slate-400">سيتم الإعلان عن التخصصات المتاحة قريباً.</p>
                @endif
            </div>
        </section>

        {{-- رحلة التقديم --}}
        <section id="news" data-spy class="reveal scroll-mt-28">
            <div class="relative z-10">
                <div class="mb-12 text-center">
                    <span class="inline-flex rounded-full bg-blue-100/80 px-4 py-2 text-sm font-bold text-blue-700 ring-1 ring-blue-200">رحلتك معنا</span>
                    <h2 class="mt-4 text-3xl font-extrabold md:text-4xl"><span class="mg-gtext">رحلة التقديم في 3 خطوات</span></h2>
                    <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">كل ما تحتاج معرفته قبل وأثناء وبعد تقديم طلبك للدراسات العليا.</p>
                </div>

                <div class="relative">
                    {{-- drawing connector line (desktop) --}}
                    <div class="mg-line pointer-events-none absolute top-7 right-[16%] left-[16%] hidden h-1 rounded-full bg-gradient-to-l from-blue-500 via-sky-400 to-teal-500 md:block" aria-hidden="true"></div>

                    @php
                        $steps = [
                            [
                                'n' => '1', 'tag' => 'قبل التقديم', 'title' => 'جهّز مستنداتك',
                                'desc' => 'شهادة البكالوريوس وكشف المواد التكميلية بصيغة PDF أو صورة (حد أقصى 5 ميجابايت لكل ملف)، مع التأكد من صحة الاسم والرقم الوطني.',
                                'grad' => 'from-blue-600 to-sky-500',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 13.5l2.25 2.25 4.5-4.5"/>',
                                'cta' => null,
                            ],
                            [
                                'n' => '2', 'tag' => 'أثناء التقديم', 'title' => 'قدّم طلبك إلكترونياً',
                                'desc' => 'عبّئ بياناتك، اختر التخصص، ارفع مستنداتك، وفعّل التحقق من رقم النقال عبر واتساب — كل ذلك أونلاين دون حضور.',
                                'grad' => 'from-sky-500 to-teal-500',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>',
                                'cta' => ['label' => 'ابدأ التقديم', 'href' => '#apply'],
                            ],
                            [
                                'n' => '3', 'tag' => 'بعد التقديم', 'title' => 'تابع حالة طلبك',
                                'desc' => 'استخدم رقم التتبع والرقم الوطني للاستعلام عن حالة القبول ومعرفة موعد اختبار القبول في أي وقت.',
                                'grad' => 'from-teal-400 to-teal-600',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>',
                                'cta' => ['label' => 'متابعة الطلب', 'href' => route('application.track.show')],
                            ],
                        ];
                    @endphp

                    {{-- ===== Mobile: vertical timeline (JS-driven reveal) ===== --}}
                    <div class="relative md:hidden" data-timeline>
                        {{-- continuous connecting line: grey track + gradient fill (positioned & grown by JS) --}}
                        <div class="pointer-events-none absolute z-0 w-1 rounded-full bg-slate-200" data-tl-track aria-hidden="true">
                            <div class="absolute inset-x-0 top-0 h-0 rounded-full bg-gradient-to-b from-blue-500 via-sky-400 to-teal-500 transition-[height] duration-700 ease-out" data-tl-fill></div>
                        </div>
                        <div class="space-y-8">
                            @foreach($steps as $step)
                                <div class="relative flex items-start gap-4">
                                    {{-- node --}}
                                    <div class="relative flex w-14 shrink-0 justify-center">
                                        <div class="tl-node relative z-10 flex size-14 items-center justify-center rounded-full bg-gradient-to-bl {{ $step['grad'] }} text-lg font-extrabold text-white shadow-lg ring-4 ring-white" data-tl-node>
                                            {{ $step['n'] }}
                                        </div>
                                    </div>
                                    {{-- card --}}
                                    <div class="mg-card bg-white relative flex-1 rounded-2xl p-5 ring-1 ring-slate-200 shadow-md">
                                        {{-- notch pointing at the line --}}
                                        <span class="absolute right-0 top-7 size-3 translate-x-1/2 rotate-45 rounded-[2px] border-r border-t border-slate-200 bg-white" aria-hidden="true"></span>
                                        <div class="relative z-10 flex flex-col items-center text-center">
                                            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-gradient-to-bl {{ $step['grad'] }} text-white">
                                                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">{!! $step['icon'] !!}</svg>
                                            </div>
                                            <span class="text-xs font-bold text-blue-700">{{ $step['tag'] }}</span>
                                            <h3 class="mt-1.5 text-base font-bold leading-snug text-slate-800">{{ $step['title'] }}</h3>
                                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $step['desc'] }}</p>
                                            @if($step['cta'])
                                                <a href="{{ $step['cta']['href'] }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-700 transition hover:gap-2 hover:text-blue-900">
                                                    {{ $step['cta']['label'] }}
                                                    <svg class="size-4 -scale-x-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== Desktop: 3-column grid ===== --}}
                    <div class="hidden gap-8 md:grid md:grid-cols-3">
                        @foreach($steps as $step)
                            <div class="flex flex-col items-center text-center">
                                {{-- node --}}
                                <div class="mg-ring relative z-10 mb-6 flex size-14 items-center justify-center rounded-full bg-gradient-to-bl {{ $step['grad'] }} text-lg font-extrabold text-white shadow-lg ring-4 ring-white">
                                    {{ $step['n'] }}
                                </div>
                                {{-- card --}}
                                <div                                     class="mg-card bg-white flex w-full flex-1 flex-col items-center rounded-2xl p-6 ring-1 ring-slate-200 shadow-md">
                                    <div class="relative z-10 flex flex-col items-center">
                                        <div class="mb-4 flex size-12 items-center justify-center rounded-2xl bg-gradient-to-bl {{ $step['grad'] }} text-white">
                                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">{!! $step['icon'] !!}</svg>
                                        </div>
                                        <span class="text-xs font-bold text-blue-700">{{ $step['tag'] }}</span>
                                        <h3 class="mt-1.5 text-base font-bold leading-snug text-slate-800">{{ $step['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $step['desc'] }}</p>
                                        @if($step['cta'])
                                            <a href="{{ $step['cta']['href'] }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-700 transition hover:gap-2 hover:text-blue-900">
                                                {{ $step['cta']['label'] }}
                                                <svg class="size-4 -scale-x-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- تواصل معنا --}}
        <section id="contact" data-spy class="reveal scroll-mt-28">
            <div class="grid items-stretch gap-6 lg:grid-cols-5">

                {{-- Left: CTA panel --}}
                <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-bl from-blue-900 via-blue-800 to-sky-700 p-8 text-white lg:col-span-2">
                    <div class="relative z-10 flex h-full flex-col">
                        <span class="inline-flex w-fit rounded-full bg-white/15 px-4 py-2 text-sm font-bold ring-1 ring-white/25">تواصل معنا</span>
                        <h2 class="mt-5 text-3xl font-extrabold leading-tight">هل لديك استفسار؟<br>نحن هنا لمساعدتك</h2>
                        <p class="mt-4 text-sky-100/90">يسعد فريق مكتب الدراسات العليا بالإجابة على استفساراتكم حول القبول والتسجيل.</p>

                        <div class="mt-auto pt-8 space-y-3">
                            <a href="https://wa.me/{{ $settings['contact_whatsapp'] }}" target="_blank" rel="noopener"
                               class="flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-3.5 font-bold text-white shadow-lg transition hover:bg-emerald-600">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.945C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-1.607zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                تواصل عبر واتساب
                            </a>
                            <a href="{{ route('application.track.show') }}"
                               class="flex items-center justify-center gap-2 rounded-xl bg-white/15 px-5 py-3.5 font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                تتبّع حالة طلبك
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Right: contact cards --}}
                <div class="grid gap-5 sm:grid-cols-2 auto-rows-fr lg:col-span-3">

                    {{-- Phone (copy) --}}
                    <div                         class="mg-card bg-white relative flex items-center gap-4 rounded-2xl p-5 ring-1 ring-slate-200 shadow-sm">
                        <div class="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        </div>
                        <div class="relative z-10 min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-slate-800">الهاتف</h3>
                            <p class="mt-1 text-sm text-slate-500 break-all" dir="ltr">{{ $settings['contact_phone'] }}</p>
                        </div>
                        <button type="button" x-data @click="navigator.clipboard?.writeText(@js($settings['contact_phone'])); $store.toast.notify('تم النسخ', @js($settings['contact_phone']), 'success')" aria-label="نسخ الهاتف"
                                class="relative z-10 shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-700">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                        </button>
                    </div>

                    {{-- Email (copy) --}}
                    <div                         class="mg-card bg-white relative flex items-center gap-4 rounded-2xl p-5 ring-1 ring-slate-200 shadow-sm">
                        <div class="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <div class="relative z-10 min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-slate-800">البريد الإلكتروني</h3>
                            <p class="mt-1 text-sm text-slate-500 break-all" dir="ltr">{{ $settings['contact_email'] }}</p>
                        </div>
                        <button type="button" x-data @click="navigator.clipboard?.writeText(@js($settings['contact_email'])); $store.toast.notify('تم النسخ', @js($settings['contact_email']), 'success')" aria-label="نسخ البريد"
                                class="relative z-10 shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-700">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25"/></svg>
                        </button>
                    </div>

                    {{-- Address --}}
                    <div                         class="mg-card bg-white relative flex items-center gap-4 rounded-2xl p-5 ring-1 ring-slate-200 shadow-sm">
                        <div class="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </div>
                        <div class="relative z-10 min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-slate-800">العنوان</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $settings['contact_address'] }}</p>
                        </div>
                    </div>

                    {{-- Working hours --}}
                    <div                         class="mg-card bg-white relative flex items-center gap-4 rounded-2xl p-5 ring-1 ring-slate-200 shadow-sm">
                        <div class="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="relative z-10 min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-slate-800">ساعات العمل</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $settings['contact_hours'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ============================ Registration form ============================ --}}
    <div id="apply" data-spy class="mx-auto max-w-5xl scroll-mt-28">

        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-flex rounded-full bg-blue-100/80 px-4 py-2 text-sm font-bold text-blue-700 ring-1 ring-blue-200">
                التسجيل الإلكتروني
            </span>
            <h2 class="mt-4 text-3xl font-extrabold md:text-4xl">
                <span class="mg-gtext">استمارة طلب التحاق بالدراسات العليا</span>
            </h2>
            <p class="mt-3 text-base text-slate-500">الإجازة العليا (الماجستير)</p>
        </div>

        {{-- Success --}}
        @if(session('success'))
            <div class="mb-8 rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 size-5 shrink-0 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-bold text-green-700">{{ session('success') }}</p>
                </div>
                @if(session('tracking_code'))
                    <div class="mt-4 rounded-xl border border-green-300 bg-white px-5 py-4 text-center">
                        <p class="text-sm text-slate-500">رقم التتبع الخاص بطلبك:</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-widest text-slate-900" dir="ltr">{{ session('tracking_code') }}</p>
                        <p class="mt-2 text-xs text-slate-400">احتفظ بهذا الرقم لمتابعة حالة طلبك</p>
                    </div>
                    <a href="{{ route('application.track.show') }}" class="mt-3 inline-block text-sm font-medium text-green-600 underline hover:text-green-800">
                        متابعة حالة الطلب ←
                    </a>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('registration.store') }}" enctype="multipart/form-data" class="space-y-8"
              x-data="applyForm()" x-init="recalc()" @input="recalc()" @change="recalc()">
            @csrf

            {{-- Live completion progress --}}
            <div class="sticky top-4 z-20 rounded-2xl border border-slate-200 bg-white/95 px-5 py-3 shadow-sm backdrop-blur">
                <div class="mb-2 flex items-center justify-between text-sm font-bold">
                    <span class="text-slate-700">اكتمال الاستمارة</span>
                    <span class="text-blue-700" x-text="progress + '%'">0%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-l from-sky-400 to-blue-700 transition-[width] duration-300 ease-out"
                         :style="`width: ${progress}%`"></div>
                </div>
            </div>

            {{-- 1. Personal Information --}}
            <div data-section="personal" class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    <span>أولاً: البيانات الشخصية</span>
                    <span x-show="sections.personal" x-cloak class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        مكتمل
                    </span>
                </h3>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Full name --}}
                    <div class="md:col-span-2">
                        <x-form.label for="name">الاسم الرباعي واللقب <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="الاسم الأول + اسم الأب + اسم الجد + اسم العائلة (اللقب)" required />
                        <p class="mt-1 text-xs text-slate-400">يجب إدخال الاسم الرباعي كاملاً بما في ذلك اللقب (اسم العائلة) باللغة العربية — مثال: محمد أحمد علي الشنيب</p>
                        <x-form.error name="name" />
                    </div>

                    {{-- English name --}}
                    <div class="md:col-span-2">
                        <x-form.label for="name_en">الاسم باللغة الإنجليزية (كما في جواز السفر) <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="name_en" name="name_en" type="text" value="{{ old('name_en') }}" placeholder="Full name as it appears in your passport" dir="ltr" required />
                        <p class="mt-1 text-xs text-slate-400">يرجى كتابة الاسم كما هو مدوّن في جواز السفر بالضبط</p>
                        <x-form.error name="name_en" />
                    </div>

                    <div>
                        <x-form.label for="mother_name">اسم الأم <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="mother_name" name="mother_name" type="text" value="{{ old('mother_name') }}" placeholder="الاسم الأول والثاني على الأقل" required />
                        <x-form.error name="mother_name" />
                    </div>

                    <div>
                        <x-form.label for="birth_date">تاريخ الميلاد <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" required />
                        <x-form.error name="birth_date" />
                    </div>

                    <div>
                        <x-form.label for="birth_place">مكان الميلاد <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="birth_place" name="birth_place" type="text" value="{{ old('birth_place') }}" required />
                        <x-form.error name="birth_place" />
                    </div>

                    <div>
                        <x-form.label for="gender">الجنس <span class="text-red-500">*</span></x-form.label>
                        <x-form.select id="gender" name="gender" required>
                            <option value="">اختر</option>
                            <option value="male" @selected(old('gender') === 'male')>ذكر</option>
                            <option value="female" @selected(old('gender') === 'female')>أنثى</option>
                        </x-form.select>
                        <x-form.error name="gender" />
                    </div>

                    <div>
                        <x-form.label for="marital_status">الحالة الاجتماعية <span class="text-red-500">*</span></x-form.label>
                        <x-form.select id="marital_status" name="marital_status" required>
                            <option value="">اختر</option>
                            <option value="single" @selected(old('marital_status') === 'single')>أعزب / عزباء</option>
                            <option value="married" @selected(old('marital_status') === 'married')>متزوج / متزوجة</option>
                            <option value="divorced" @selected(old('marital_status') === 'divorced')>مطلق / مطلقة</option>
                            <option value="widowed" @selected(old('marital_status') === 'widowed')>أرمل / أرملة</option>
                        </x-form.select>
                        <x-form.error name="marital_status" />
                    </div>

                    {{-- Nationality combo --}}
                    <div>
                        <x-form.label for="nationality">الجنسية <span class="text-red-500">*</span></x-form.label>
                        <x-form.select id="nationality" name="nationality" x-model="nationality" required>
                            <option value="ليبية" @selected(old('nationality', 'ليبية') !== 'أخرى')>ليبية</option>
                            <option value="أخرى" @selected(old('nationality') === 'أخرى')>أخرى</option>
                        </x-form.select>
                        <x-form.error name="nationality" />
                    </div>

                    <div x-show="isOther" x-cloak x-transition>
                        <x-form.label for="nationality_other">حدد الجنسية <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="nationality_other" name="nationality_other" type="text" value="{{ old('nationality_other') }}" placeholder="أدخل الجنسية بالعربية" ::required="isOther" />
                        <x-form.error name="nationality_other" />
                    </div>

                    <div>
                        <x-form.label for="national_id">الرقم الوطني <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="national_id" name="national_id" type="text" value="{{ old('national_id') }}" placeholder="12 رقماً" maxlength="12" required x-model="nationalId" dir="ltr" class="text-right" />
                        <p class="mt-1 text-xs" :class="nationalId.length === 12 ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                            <span x-text="nationalId.length + '/12'">0/12</span>
                            <span x-show="nationalId.length === 12">✓</span>
                        </p>
                        <x-form.error name="national_id" />
                    </div>

                    <div>
                        <x-form.label for="card_number">رقم البطاقة</x-form.label>
                        <x-form.input id="card_number" name="card_number" type="text" value="{{ old('card_number') }}" />
                        <x-form.error name="card_number" />
                    </div>

                    <div>
                        <x-form.label for="residence">مكان الإقامة <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="residence" name="residence" type="text" value="{{ old('residence') }}" required />
                        <x-form.error name="residence" />
                    </div>

                    <div>
                        <x-form.label for="permanent_address">العنوان الدائم <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="permanent_address" name="permanent_address" type="text" value="{{ old('permanent_address') }}" required />
                        <x-form.error name="permanent_address" />
                    </div>

                    <div>
                        <x-form.label for="profession">المهنة</x-form.label>
                        <x-form.input id="profession" name="profession" type="text" value="{{ old('profession') }}" />
                        <x-form.error name="profession" />
                    </div>

                    <div>
                        <x-form.label for="workplace">مكان العمل</x-form.label>
                        <x-form.input id="workplace" name="workplace" type="text" value="{{ old('workplace') }}" />
                        <x-form.error name="workplace" />
                    </div>
                </div>
            </div>

            {{-- 2. Contact & Academic --}}
            <div data-section="contact" class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    <span>ثانياً: بيانات التواصل والقسم</span>
                    <span x-show="sections.contact" x-cloak class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        مكتمل
                    </span>
                </h3>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <x-form.label for="email">البريد الإلكتروني <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="example@email.com" required />
                        <x-form.error name="email" />
                    </div>

                    <div>
                        <x-form.label for="phone">رقم النقال <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="09xxxxxxxx" maxlength="10" required x-model="phone" dir="ltr" class="text-right" />
                        <p class="mt-1 flex items-center justify-between text-xs" :class="phone && ! phoneValid ? 'text-red-500' : 'text-slate-400'">
                            <span>
                                <span x-show="! phone">10 أرقام يبدأ بـ 09 (ليبي فقط)</span>
                                <span x-show="phone && ! phoneValid" x-cloak>الرقم يجب أن يكون 10 أرقام ويبدأ بـ 09</span>
                                <span x-show="phoneValid" x-cloak class="font-bold text-emerald-600">✓ رقم صحيح</span>
                            </span>
                            <span class="tabular-nums" :class="phoneValid && 'font-bold text-emerald-600'" x-text="phone.length + '/10'">0/10</span>
                        </p>
                        <x-form.error name="phone" />

                        @if($whatsappEnabled)
                            <div id="otp-widget" class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <button type="button" id="otp-send-btn"
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 transition disabled:opacity-50">
                                    <span id="otp-send-label">إرسال رمز التحقق عبر واتساب</span>
                                </button>

                                <div id="otp-verify-row" class="mt-2 hidden items-center gap-2">
                                    <input type="text" id="otp-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                                        placeholder="رمز التحقق"
                                        class="w-32 rounded-lg border border-slate-300 px-3 py-1.5 text-sm tracking-widest" />
                                    <button type="button" id="otp-verify-btn"
                                        class="rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-800 transition disabled:opacity-50">
                                        تأكيد الرمز
                                    </button>
                                </div>

                                <p id="otp-status" class="mt-2 text-xs text-slate-500"></p>
                                <span id="otp-verified-badge" class="mt-1 hidden text-xs font-bold text-emerald-700">✓ تم التحقق من رقم النقال</span>
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <x-form.label for="specialization_id">القسم / التخصص المطلوب <span class="text-red-500">*</span></x-form.label>
                        <x-form.select id="specialization_id" name="specialization_id" required>
                            <option value="">اختر التخصص</option>
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}" @selected(old('specialization_id') == $specialization->id)>
                                    {{ $specialization->name }}
                                </option>
                            @endforeach
                        </x-form.select>
                        <x-form.error name="specialization_id" />
                    </div>

                    <div>
                        <x-form.label for="password">كلمة المرور <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="password" name="password" type="password" placeholder="8 أحرف على الأقل" required x-model="password" />
                        <p x-show="password && password.length < 8" x-cloak class="mt-1 text-xs text-red-500">كلمة المرور يجب أن تكون 8 أحرف على الأقل</p>
                        <x-form.error name="password" />
                    </div>

                    <div>
                        <x-form.label for="password_confirmation">تأكيد كلمة المرور <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="password_confirmation" name="password_confirmation" type="password" placeholder="أعد كتابة كلمة المرور" required x-model="passwordConfirm" />
                        <p x-show="passwordConfirm && ! passwordsMatch" x-cloak class="mt-1 text-xs text-red-500">كلمتا المرور غير متطابقتين</p>
                        <p x-show="passwordConfirm && passwordsMatch" x-cloak class="mt-1 text-xs font-bold text-emerald-600">✓ كلمتا المرور متطابقتان</p>
                    </div>
                </div>
            </div>

            {{-- 3. Study Type --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    ثالثاً: نوع الدراسة <span class="text-red-500">*</span>
                </h3>

                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="self_funded" x-model="studyType" @checked(old('study_type') === 'self_funded') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">الدراسة على حسابي الخاص</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="internally_delegated" x-model="studyType" @checked(old('study_type') === 'internally_delegated') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">موفد بموجب قرار إيفاد داخلي</span>
                    </label>

                    <div x-show="showDelegationNumber" x-cloak x-transition class="ps-12">
                        <x-form.label for="delegation_number">رقم قرار الإيفاد</x-form.label>
                        <x-form.input id="delegation_number" name="delegation_number" type="text" value="{{ old('delegation_number') }}" class="max-w-xs" />
                        <x-form.error name="delegation_number" />
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="faculty_assistant" x-model="studyType" @checked(old('study_type') === 'faculty_assistant') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">معيد بالكلية</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="public_delegation" x-model="studyType" @checked(old('study_type') === 'public_delegation') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">موفد من قِبَل جهة عامة</span>
                    </label>

                    <div x-show="showDelegationEntity" x-cloak x-transition class="ps-12">
                        <x-form.label for="delegation_entity">اسم الجهة المُوفِدة</x-form.label>
                        <x-form.input id="delegation_entity" name="delegation_entity" type="text" value="{{ old('delegation_entity') }}" />
                        <x-form.error name="delegation_entity" />
                    </div>
                </div>

                <x-form.error name="study_type" />
            </div>

            {{-- 4. Documents --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-1 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    رابعاً: المستندات المطلوبة
                </h3>
                <p class="mb-6 text-sm text-slate-500">الصيغ المقبولة: PDF، JPG، PNG — الحجم الأقصى 5 ميجابايت لكل ملف</p>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <x-form.label for="bachelor_certificate">شهادة البكالوريوس</x-form.label>
                        <input id="bachelor_certificate" name="bachelor_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png"
                            @change="checkFile($event)"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-600 focus:ring-2 focus:ring-blue-200" />
                        <x-form.error name="bachelor_certificate" />
                    </div>

                    <div>
                        <x-form.label for="remedial_statement">كشف المواد التكميلية</x-form.label>
                        <input id="remedial_statement" name="remedial_statement" type="file" accept=".pdf,.jpg,.jpeg,.png"
                            @change="checkFile($event)"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-600 focus:ring-2 focus:ring-blue-200" />
                        <x-form.error name="remedial_statement" />
                    </div>
                </div>
            </div>

            {{-- Declaration & Submit --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">

                {{-- Declaration checkbox --}}
                <div class="mb-6 rounded-xl border-2 border-blue-200 bg-blue-50 p-5">
                    <label class="flex cursor-pointer items-start gap-4">
                        <input
                            type="checkbox"
                            name="declaration"
                            value="1"
                            @checked(old('declaration'))
                            class="mt-1 size-5 shrink-0 cursor-pointer rounded accent-blue-600"
                        />
                        <span class="text-base font-semibold leading-relaxed text-slate-800">
                            أقر بصحة البيانات المذكورة أعلاه، وكما أقر بأني غير مسجل للدراسات العليا بأي كلية داخل ليبيا.
                        </span>
                    </label>
                    <x-form.error name="declaration" />
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        قدمت طلباً سابقاً؟
                        <a href="{{ route('application.track.show') }}" class="font-medium text-blue-600 hover:underline">متابعة حالة الطلب</a>
                    </p>
                    <x-form.button type="submit" id="submit-btn">
                        إرسال الطلب
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Alpine component backing the application form: conditional fields
        // and live phone/password validation.
        function applyForm() {
            return {
                nationality: @js(old('nationality', 'ليبية')),
                studyType: @js(old('study_type', '')),
                phone: @js(old('phone', '')),
                nationalId: @js(old('national_id', '')),
                password: '',
                passwordConfirm: '',

                progress: 0,
                sections: {},

                // Recompute the completion progress bar + per-section badges by
                // scanning the form's required fields. Called on init + every input.
                recalc() {
                    const form = this.$root;
                    const filled = (el) => el.type === 'checkbox'
                        ? el.checked
                        : String(el.value || '').trim() !== '';

                    const required = [...form.querySelectorAll('[required]')];
                    this.progress = required.length
                        ? Math.round(required.filter(filled).length / required.length * 100)
                        : 0;

                    const secs = {};
                    form.querySelectorAll('[data-section]').forEach((sec) => {
                        const req = [...sec.querySelectorAll('[required]')];
                        secs[sec.dataset.section] = req.length > 0 && req.every(filled);
                    });
                    this.sections = secs;
                },

                // Reject oversized uploads on the client (5 MB/file) with a clear
                // alert, then clear the input so a huge file is never submitted.
                // The server (max:5120) re-validates as the real guard.
                maxFileBytes: 5 * 1024 * 1024,
                checkFile(event) {
                    const input = event.target;
                    const file = input.files && input.files[0];
                    if (file && file.size > this.maxFileBytes) {
                        const mb = (file.size / 1024 / 1024).toFixed(1);
                        this.$store.toast.notify(
                            'حجم الملف كبير جداً',
                            `حجم الملف ${mb} ميجابايت ويتجاوز الحد الأقصى المسموح به وهو 5 ميجابايت. يرجى اختيار ملف أصغر.`,
                            'error',
                        );
                        input.value = '';
                    }
                },

                get isOther() {
                    return this.nationality === 'أخرى';
                },
                get showDelegationNumber() {
                    return this.studyType === 'internally_delegated';
                },
                get showDelegationEntity() {
                    return this.studyType === 'faculty_assistant' || this.studyType === 'public_delegation';
                },
                get phoneValid() {
                    return /^09\d{8}$/.test(this.phone);
                },
                get passwordsMatch() {
                    return this.password === this.passwordConfirm;
                },
            };
        }
    </script>

    @if($whatsappEnabled)
    <script>
        // WhatsApp OTP phone verification. The applicant must verify their
        // phone via a code sent over WhatsApp before the form can be submitted.
        // The server (RegistrationController::store) re-checks verification, so
        // this is a UX guard, not the security boundary.
        (() => {
            const phoneInput = document.getElementById('phone');
            const sendBtn = document.getElementById('otp-send-btn');
            const sendLabel = document.getElementById('otp-send-label');
            const verifyRow = document.getElementById('otp-verify-row');
            const codeInput = document.getElementById('otp-code');
            const verifyBtn = document.getElementById('otp-verify-btn');
            const status = document.getElementById('otp-status');
            const badge = document.getElementById('otp-verified-badge');
            const submitBtn = document.getElementById('submit-btn');
            const csrf = document.querySelector('input[name="_token"]')?.value;

            let verified = false;
            let cooldownTimer = null;

            const setStatus = (msg, ok = false) => {
                status.textContent = msg ?? '';
                status.className = 'mt-2 text-xs ' + (ok ? 'text-emerald-700' : 'text-red-600');
            };

            const lockSubmit = () => {
                if (! verified) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            };

            const unlockSubmit = () => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            };

            // Verification is bound to a specific number — changing it resets state.
            const resetVerification = () => {
                verified = false;
                badge.classList.add('hidden');
                lockSubmit();
            };

            const post = async (url, body) => {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                return { ok: res.ok, data };
            };

            const startCooldown = (seconds) => {
                let remaining = seconds;
                sendBtn.disabled = true;
                const tick = () => {
                    sendLabel.textContent = `إعادة الإرسال بعد ${remaining} ثانية`;
                    if (remaining <= 0) {
                        clearInterval(cooldownTimer);
                        sendBtn.disabled = false;
                        sendLabel.textContent = 'إعادة إرسال الرمز';
                        return;
                    }
                    remaining--;
                };
                tick();
                cooldownTimer = setInterval(tick, 1000);
            };

            phoneInput.addEventListener('input', resetVerification);

            sendBtn.addEventListener('click', async () => {
                const phone = phoneInput.value.trim();
                if (! /^09\d{8}$/.test(phone)) {
                    setStatus('أدخل رقم نقال صحيح (10 أرقام يبدأ بـ 09) أولاً.');
                    return;
                }
                sendBtn.disabled = true;
                setStatus('جاري الإرسال...');
                try {
                    const { ok, data } = await post('{{ route('registration.otp.send') }}', { phone });
                    setStatus(data.message ?? (ok ? 'تم الإرسال.' : 'تعذّر الإرسال.'), ok);
                    if (ok) {
                        verifyRow.classList.remove('hidden');
                        verifyRow.classList.add('flex');
                        codeInput.focus();
                        startCooldown(60);
                    } else {
                        sendBtn.disabled = false;
                        if (data.retry_after) startCooldown(data.retry_after);
                    }
                } catch (e) {
                    setStatus('حدث خطأ في الاتصال. حاول مرة أخرى.');
                    sendBtn.disabled = false;
                }
            });

            verifyBtn.addEventListener('click', async () => {
                const phone = phoneInput.value.trim();
                const code = codeInput.value.trim();
                if (! code) {
                    setStatus('أدخل رمز التحقق.');
                    return;
                }
                verifyBtn.disabled = true;
                setStatus('جاري التحقق...');
                try {
                    const { ok, data } = await post('{{ route('registration.otp.verify') }}', { phone, code });
                    if (ok && data.verified) {
                        verified = true;
                        setStatus('', true);
                        badge.classList.remove('hidden');
                        verifyRow.classList.add('hidden');
                        sendBtn.classList.add('hidden');
                        if (cooldownTimer) clearInterval(cooldownTimer);
                        unlockSubmit();
                    } else {
                        setStatus(data.message ?? 'الرمز غير صحيح.');
                    }
                } catch (e) {
                    setStatus('حدث خطأ في الاتصال. حاول مرة أخرى.');
                } finally {
                    verifyBtn.disabled = false;
                }
            });

            // Gate submission until verified.
            lockSubmit();
        })();
    </script>
    @endif

    {{-- Mobile "رحلة التقديم" timeline: connect the line + reveal nodes 1→2→3 on scroll --}}
    <script>
        (function () {
            function initJourneyTimeline() {
                const tl = document.querySelector('[data-timeline]');
                if (!tl) { return; }
                const nodes = Array.prototype.slice.call(tl.querySelectorAll('[data-tl-node]'));
                const track = tl.querySelector('[data-tl-track]');
                const fill = tl.querySelector('[data-tl-fill]');
                if (!nodes.length || !track || !fill) { return; }

                const LINE_W = 4;
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                function centerY(el) {
                    const r = el.getBoundingClientRect();
                    const t = tl.getBoundingClientRect();
                    return (r.top + r.height / 2) - t.top;
                }

                function layout() {
                    if (getComputedStyle(tl).display === 'none') { return; }
                    const t = tl.getBoundingClientRect();
                    const n0 = nodes[0].getBoundingClientRect();
                    const rightOffset = t.right - (n0.left + n0.width / 2) - (LINE_W / 2);
                    const topY = centerY(nodes[0]);
                    const botY = centerY(nodes[nodes.length - 1]);
                    track.style.right = rightOffset + 'px';
                    track.style.top = topY + 'px';
                    track.style.height = Math.max(0, botY - topY) + 'px';
                }

                let activeIndex = -1;
                function grow(i) {
                    if (i <= activeIndex) { return; }
                    activeIndex = i;
                    const topY = centerY(nodes[0]);
                    const tgtY = centerY(nodes[i]);
                    fill.style.height = Math.max(0, tgtY - topY) + 'px';
                }

                layout();
                window.addEventListener('resize', layout, { passive: true });
                window.addEventListener('load', layout);

                if (reduce || !('IntersectionObserver' in window)) {
                    nodes.forEach(function (n) { n.classList.add('is-on'); });
                    grow(nodes.length - 1);
                    return;
                }

                const io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            const i = nodes.indexOf(e.target);
                            e.target.classList.add('is-on');
                            grow(i);
                            io.unobserve(e.target);
                        }
                    });
                }, { rootMargin: '0px 0px -25% 0px', threshold: 0.5 });
                nodes.forEach(function (n) { io.observe(n); });
            }

            if (document.readyState !== 'loading') {
                initJourneyTimeline();
            } else {
                document.addEventListener('DOMContentLoaded', initJourneyTimeline);
            }
        })();
    </script>
</x-layout.app>
