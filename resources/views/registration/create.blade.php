<x-layout.app title="طلب الالتحاق بالدراسات العليا">
    <div class="mx-auto max-w-5xl">

        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                التسجيل الإلكتروني
            </span>
            <h2 class="mt-4 text-4xl font-extrabold text-slate-900">
                استمارة طلب التحاق بالدراسات العليا
            </h2>
            <p class="mt-3 text-base text-slate-500">الإجازة العليا (الماجستير)</p>

            {{-- Testing helper: generate sample applicant data --}}
            <div class="mt-6 inline-flex">
                <button type="button"
                        x-data
                        @click="$dispatch('fill-sample')"
                        :disabled="$store.apply?.filling"
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm transition hover:bg-amber-100 hover:text-amber-800 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg class="size-4" :class="$store.apply?.filling && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 00-3.09 3.09zM18 7.5v3m0 0v3m0-3h3m-3 0h-3" />
                    </svg>
                    <span x-text="$store.apply?.filling ? 'جاري التوليد...' : 'توليد بيانات تجريبية'">توليد بيانات تجريبية</span>
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-400">يملأ الاستمارة ببيانات عشوائية للاختبار — ما عدا الملفات (يجب اختيارها يدوياً).</p>
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
              x-data="applyForm()" @fill-sample.window="fillSample()">
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
        // Alpine store shared with the "fill sample" button (which lives in the
        // page header, outside the form's x-data scope).
        document.addEventListener('alpine:init', () => {
            Alpine.store('apply', { filling: false });
        });

        // Alpine component backing the application form: conditional fields,
        // live phone/password validation, and the test-data filler.
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

                // Fetch sample applicant data and fill every matching field.
                // File inputs are skipped (browser security) and the declaration
                // checkbox is auto-ticked. Dispatching input/change keeps the
                // Alpine-bound fields (nationality, study_type, phone, …) in sync.
                async fillSample() {
                    this.$store.apply.filling = true;
                    try {
                        const response = await fetch('{{ route('registration.sample') }}', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (! response.ok) throw new Error('sample endpoint returned ' + response.status);

                        const data = await response.json();

                        for (const [name, value] of Object.entries(data)) {
                            const radios = document.querySelectorAll(`input[name="${name}"][type="radio"]`);
                            if (radios.length > 0) {
                                radios.forEach(r => {
                                    r.checked = String(r.value) === String(value);
                                    r.dispatchEvent(new Event('change', { bubbles: true }));
                                });
                                continue;
                            }
                            const el = document.querySelector(`[name="${name}"]`);
                            if (! el) continue;
                            if (el.type === 'file') continue;
                            if (el.type === 'checkbox') { el.checked = !! value; continue; }
                            el.value = value ?? '';
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        const decl = document.querySelector('input[name="declaration"]');
                        if (decl) decl.checked = true;
                    } catch (err) {
                        console.error(err);
                        alert('فشل توليد البيانات. راجع الكونسول.');
                    } finally {
                        this.$store.apply.filling = false;
                    }
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
