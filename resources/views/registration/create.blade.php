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
        </div>

        {{-- Success --}}
        @if(session('success'))
            <div class="mb-8 flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4">
                <svg class="mt-0.5 size-5 shrink-0 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-bold text-green-700">{{ session('success') }}</p>
                    <a href="{{ url('/portal/login') }}" class="mt-1 inline-block text-sm font-medium text-green-600 underline hover:text-green-800">
                        الذهاب إلى بوابة الطالب ←
                    </a>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('registration.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- 1. Personal Information --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    أولاً: البيانات الشخصية
                </h3>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Full name --}}
                    <div class="md:col-span-2">
                        <x-form.label for="name">اسم الطالب الرباعي <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="الاسم الأول والثاني والثالث والرابع (بالعربية)" required />
                        <p class="mt-1 text-xs text-slate-400">يجب إدخال أربعة أسماء على الأقل باللغة العربية</p>
                        <x-form.error name="name" />
                    </div>

                    <div>
                        <x-form.label for="last_name">اللقب <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" placeholder="اللقب العائلي بالعربية" required />
                        <x-form.error name="last_name" />
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
                        <x-form.select id="nationality" name="nationality" required>
                            <option value="ليبية" @selected(old('nationality', 'ليبية') !== 'أخرى')>ليبية</option>
                            <option value="أخرى" @selected(old('nationality') === 'أخرى')>أخرى</option>
                        </x-form.select>
                        <x-form.error name="nationality" />
                    </div>

                    <div id="nationality_other_row" class="{{ old('nationality') === 'أخرى' ? '' : 'hidden' }}">
                        <x-form.label for="nationality_other">حدد الجنسية <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="nationality_other" name="nationality_other" type="text" value="{{ old('nationality_other') }}" placeholder="أدخل الجنسية بالعربية" />
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

                    <div class="md:col-span-2">
                        <x-form.label>هل أنت منتقل من كلية أخرى؟ <span class="text-red-500">*</span></x-form.label>
                        <div class="mt-2 flex gap-6">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="is_transfer" value="0" @checked(old('is_transfer', '0') === '0') class="size-4 accent-blue-600" />
                                <span class="font-medium text-slate-700">لا</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="is_transfer" value="1" @checked(old('is_transfer') === '1') class="size-4 accent-blue-600" />
                                <span class="font-medium text-slate-700">نعم</span>
                            </label>
                        </div>
                        <x-form.error name="is_transfer" />
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
                        <x-form.input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="09xxxxxxxx" maxlength="10" required />
                        <p class="mt-1 text-xs text-slate-400">10 أرقام يبدأ بـ 09 (ليبي فقط)</p>
                        <x-form.error name="phone" />
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
                </div>
            </div>

            {{-- 3. Study Type --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    ثالثاً: نوع الدراسة <span class="text-red-500">*</span>
                </h3>

                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="self_funded" @checked(old('study_type') === 'self_funded') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">الدراسة على حسابي الخاص</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="internally_delegated" @checked(old('study_type') === 'internally_delegated') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">موفد بموجب قرار إيفاد داخلي</span>
                    </label>

                    <div id="delegation_number_row" class="hidden ps-12">
                        <x-form.label for="delegation_number">رقم قرار الإيفاد</x-form.label>
                        <x-form.input id="delegation_number" name="delegation_number" type="text" value="{{ old('delegation_number') }}" class="max-w-xs" />
                        <x-form.error name="delegation_number" />
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="faculty_assistant" @checked(old('study_type') === 'faculty_assistant') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">معيد بالكلية</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-5 py-4 transition hover:bg-blue-50 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                        <input type="radio" name="study_type" value="public_delegation" @checked(old('study_type') === 'public_delegation') class="size-4 accent-blue-600" />
                        <span class="text-base font-medium text-slate-700">موفد من قِبَل جهة عامة</span>
                    </label>

                    <div id="delegation_entity_row" class="hidden ps-12">
                        <x-form.label for="delegation_entity">اسم الجهة المُوفِدة</x-form.label>
                        <x-form.input id="delegation_entity" name="delegation_entity" type="text" value="{{ old('delegation_entity') }}" />
                        <x-form.error name="delegation_entity" />
                    </div>
                </div>

                <x-form.error name="study_type" />
            </div>

            {{-- 4. Password --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-6 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    رابعاً: كلمة مرور بوابة الطالب
                </h3>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <x-form.label for="password">كلمة المرور <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="password" name="password" type="password" placeholder="8 أحرف على الأقل" required />
                        <x-form.error name="password" />
                    </div>

                    <div>
                        <x-form.label for="password_confirmation">تأكيد كلمة المرور <span class="text-red-500">*</span></x-form.label>
                        <x-form.input id="password_confirmation" name="password_confirmation" type="password" placeholder="أعد إدخال كلمة المرور" required />
                    </div>
                </div>
            </div>

            {{-- 5. Documents --}}
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-slate-200">
                <h3 class="mb-1 border-b border-slate-100 pb-4 text-lg font-bold text-slate-800">
                    خامساً: المستندات المطلوبة
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
                        لديك حساب بالفعل؟
                        <a href="{{ url('/portal/login') }}" class="font-medium text-blue-600 hover:underline">تسجيل الدخول</a>
                    </p>
                    <x-form.button type="submit">
                        إرسال الطلب
                    </x-form.button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Nationality combo: show/hide custom nationality input
        function updateNationalityField() {
            const val = document.getElementById('nationality').value;
            const row = document.getElementById('nationality_other_row');
            const input = document.getElementById('nationality_other');
            const isOther = val === 'أخرى';
            row.classList.toggle('hidden', ! isOther);
            input.required = isOther;
        }

        document.getElementById('nationality').addEventListener('change', updateNationalityField);
        updateNationalityField();

        // Study type: show/hide delegation fields
        function updateDelegationFields() {
            const selected = document.querySelector('input[name="study_type"]:checked')?.value;
            document.getElementById('delegation_number_row').classList.toggle('hidden', selected !== 'internally_delegated');
            document.getElementById('delegation_entity_row').classList.toggle('hidden',
                selected !== 'faculty_assistant' && selected !== 'public_delegation');
        }

        document.querySelectorAll('input[name="study_type"]').forEach(el => {
            el.addEventListener('change', updateDelegationFields);
        });

        updateDelegationFields();
    </script>
</x-layout.app>
