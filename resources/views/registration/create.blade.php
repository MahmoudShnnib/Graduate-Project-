<x-layout.app title="طلب الالتحاق بالدراسات العليا">

    @php($settings = \App\Models\Setting::allKeyed())

    {{-- ============================ Landing sections ============================ --}}
    <div class="space-y-20 mb-20">

        {{-- عن الكلية --}}
        <section id="about" class="scroll-mt-28">
            <div class="mb-10 text-center">
                <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">عن الكلية</span>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 md:text-4xl">نبذة عن كليتنا</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">
                    {{ $settings['college_name'] }} — مؤسسة تعليمية رائدة في مجال التقنية الإلكترونية، تسعى لإعداد كوادر مؤهلة قادرة على مواكبة التطور الرقمي وخدمة المجتمع.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-8 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-slate-800">رؤيتنا</h3>
                    <p class="text-sm leading-relaxed text-slate-500">الريادة في التعليم التقني والإسهام في بناء مجتمع المعرفة الرقمي.</p>
                </div>
                <div class="rounded-2xl bg-white p-8 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-slate-800">رسالتنا</h3>
                    <p class="text-sm leading-relaxed text-slate-500">إعداد خريجين متميزين علمياً وعملياً وفق أحدث المعايير الأكاديمية.</p>
                </div>
                <div class="rounded-2xl bg-white p-8 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-slate-800">أهدافنا</h3>
                    <p class="text-sm leading-relaxed text-slate-500">تطوير البحث العلمي وربط مخرجات التعليم باحتياجات سوق العمل.</p>
                </div>
            </div>
        </section>

        {{-- هيئة التدريس --}}
        <section id="faculty" class="scroll-mt-28">
            <div class="mb-10 text-center">
                <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">هيئة التدريس</span>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 md:text-4xl">قيادة وكوادر أكاديمية متميزة</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">
                    نخبة من أعضاء هيئة التدريس ذوي الخبرة والكفاءة يشرفون على العملية التعليمية والبحثية بالكلية.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="flex items-center gap-5 rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-200">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-blue-700 text-2xl font-extrabold text-white">
                        {{ mb_substr(trim($settings['dean_name'] ?? ''), 0, 1) ?: 'ع' }}
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-blue-700">عميد الكلية</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-800">{{ $settings['dean_name'] }}</h3>
                    </div>
                </div>
                <div class="flex items-center gap-5 rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-200">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-sky-600 text-2xl font-extrabold text-white">
                        {{ mb_substr(trim($settings['director_name'] ?? ''), 0, 1) ?: 'م' }}
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-sky-700">{{ $settings['office_name'] }}</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-800">{{ $settings['director_name'] }}</h3>
                    </div>
                </div>
            </div>
        </section>

        {{-- الأقسام --}}
        <section id="departments" class="scroll-mt-28">
            <div class="mb-10 text-center">
                <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">الأقسام</span>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 md:text-4xl">الأقسام والتخصصات</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">
                    تطرح الكلية برامج الدراسات العليا (الماجستير) في التخصصات التالية:
                </p>
            </div>

            @if($specializations->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($specializations as $specialization)
                        <div class="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg hover:ring-blue-300">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 transition group-hover:bg-blue-700 group-hover:text-white">
                                    <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                                </div>
                                @if($specialization->code)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500" dir="ltr">#{{ $specialization->code }}</span>
                                @endif
                            </div>
                            <h3 class="mb-3 text-lg font-bold text-slate-800">{{ $specialization->name }}</h3>
                            <a href="#apply" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:text-blue-900">
                                قدّم على هذا التخصص
                                <svg class="size-4 -scale-x-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-slate-400">سيتم الإعلان عن التخصصات المتاحة قريباً.</p>
            @endif
        </section>

        {{-- الأخبار --}}
        {{-- NOTE: placeholder news — replace with real announcements when available. --}}
        <section id="news" class="scroll-mt-28">
            <div class="mb-10 text-center">
                <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">الأخبار</span>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 md:text-4xl">آخر الأخبار والإعلانات</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">تابع أحدث المستجدات والإعلانات الخاصة بالدراسات العليا.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach([
                    ['date' => 'فتح باب التقديم', 'title' => 'بدء استقبال طلبات الالتحاق بالدراسات العليا', 'excerpt' => 'تعلن الكلية عن فتح باب التقديم لبرامج الماجستير للعام الدراسي الجديد عبر المنصة الإلكترونية.'],
                    ['date' => 'مواعيد الاختبارات', 'title' => 'الإعلان عن جدول اختبارات القبول', 'excerpt' => 'سيتم إخطار المتقدمين المقبولين مبدئياً بمواعيد اختبارات القبول عبر رقم التتبع والبريد الإلكتروني.'],
                    ['date' => 'إرشادات التسجيل', 'title' => 'دليل المتقدم لاستكمال الطلب', 'excerpt' => 'يرجى من المتقدمين تجهيز المستندات المطلوبة والتأكد من صحة البيانات قبل إرسال الطلب.'],
                ] as $news)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200 transition hover:shadow-lg">
                        <div class="flex h-32 items-center justify-center bg-gradient-to-bl from-blue-700 to-sky-600 text-white">
                            <svg class="size-12 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                        </div>
                        <div class="p-6">
                            <span class="text-xs font-bold text-blue-700">{{ $news['date'] }}</span>
                            <h3 class="mt-2 text-base font-bold leading-snug text-slate-800">{{ $news['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $news['excerpt'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- اتصل بنا --}}
        {{-- NOTE: placeholder contact details — replace phone/email/address with real values. --}}
        <section id="contact" class="scroll-mt-28">
            <div class="mb-10 text-center">
                <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">اتصل بنا</span>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 md:text-4xl">تواصل معنا</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-500">نسعد بالإجابة على استفساراتكم حول القبول والتسجيل بالدراسات العليا.</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <h3 class="mb-1 text-sm font-bold text-slate-800">العنوان</h3>
                    <p class="text-sm text-slate-500">طرابلس — ليبيا</p>
                </div>
                <div class="rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    </div>
                    <h3 class="mb-1 text-sm font-bold text-slate-800">الهاتف</h3>
                    <p class="text-sm text-slate-500" dir="ltr">+218 21 000 0000</p>
                </div>
                <div class="rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    </div>
                    <h3 class="mb-1 text-sm font-bold text-slate-800">البريد الإلكتروني</h3>
                    <p class="text-sm text-slate-500" dir="ltr">info@cet.edu.ly</p>
                </div>
                <div class="rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-slate-200">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mb-1 text-sm font-bold text-slate-800">ساعات العمل</h3>
                    <p class="text-sm text-slate-500">الأحد — الخميس: 9ص — 3م</p>
                </div>
            </div>
        </section>
    </div>

    {{-- ============================ Registration form ============================ --}}
    <div id="apply" class="mx-auto max-w-5xl scroll-mt-28">

        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                التسجيل الإلكتروني
            </span>
            <h2 class="mt-4 text-4xl font-extrabold text-slate-900">
                استمارة طلب التحاق بالدراسات العليا
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
              x-data="applyForm()">
            @csrf

            {{-- 1. Personal Information --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    أولاً: البيانات الشخصية
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
                        <x-form.input id="national_id" name="national_id" type="text" value="{{ old('national_id') }}" placeholder="12 رقماً" maxlength="12" required />
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
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    ثانياً: بيانات التواصل والقسم
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
                        <p class="mt-1 text-xs" :class="phone && ! phoneValid ? 'text-red-500' : 'text-slate-400'">
                            <span x-show="! phone">10 أرقام يبدأ بـ 09 (ليبي فقط)</span>
                            <span x-show="phone && ! phoneValid" x-cloak>الرقم يجب أن يكون 10 أرقام ويبدأ بـ 09</span>
                            <span x-show="phoneValid" x-cloak class="font-bold text-emerald-600">✓ رقم صحيح</span>
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
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-600 focus:ring-2 focus:ring-blue-200" />
                        <x-form.error name="bachelor_certificate" />
                    </div>

                    <div>
                        <x-form.label for="remedial_statement">كشف المواد التكميلية</x-form.label>
                        <input id="remedial_statement" name="remedial_statement" type="file" accept=".pdf,.jpg,.jpeg,.png"
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
        // Alpine component backing the application form: conditional fields and
        // live phone/password validation.
        function applyForm() {
            return {
                nationality: @js(old('nationality', 'ليبية')),
                studyType: @js(old('study_type', '')),
                phone: @js(old('phone', '')),
                password: '',
                passwordConfirm: '',

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
</x-layout.app>
