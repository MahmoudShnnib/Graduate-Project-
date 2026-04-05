<x-layout.app title="التسجيل للدراسات العليا">
    <div class="mx-auto max-w-5xl">

        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                التسجيل الإلكتروني
            </span>
            <h2 class="mt-4 text-4xl font-extrabold text-slate-900">
                طلب الالتحاق ببرامج الدراسات العليا
            </h2>
            <p class="mt-4 text-lg text-slate-600">
                يرجى تعبئة النموذج التالي بدقة، وسيتم إرسال الطلب إلى مكتب الدراسات العليا للمراجعة.
            </p>
        </div>

        {{-- Success message --}}
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

        <div class="rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
            <form method="POST" action="{{ route('registration.store') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf

                {{-- Section: Personal Info --}}
                <div>
                    <h3 class="mb-4 border-b border-slate-100 pb-3 text-lg font-bold text-slate-800">
                        المعلومات الشخصية
                    </h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <x-form.label for="name">الاسم الكامل</x-form.label>
                            <x-form.input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                placeholder="أدخل اسمك الرباعي"
                                required
                            />
                            <x-form.error name="name" />
                        </div>

                        <div>
                            <x-form.label for="email">البريد الإلكتروني</x-form.label>
                            <x-form.input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="example@email.com"
                                required
                            />
                            <x-form.error name="email" />
                        </div>

                        <div>
                            <x-form.label for="phone">رقم الهاتف</x-form.label>
                            <x-form.input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                placeholder="09xxxxxxxx"
                                required
                            />
                            <x-form.error name="phone" />
                        </div>

                        <div>
                            <x-form.label for="specialization_id">التخصص المطلوب</x-form.label>
                            <x-form.select id="specialization_id" name="specialization_id" required>
                                <option value="">اختر التخصص</option>
                                @foreach($specializations as $specialization)
                                    <option
                                        value="{{ $specialization->id }}"
                                        @selected(old('specialization_id') == $specialization->id)
                                    >
                                        {{ $specialization->name }}
                                    </option>
                                @endforeach
                            </x-form.select>
                            <x-form.error name="specialization_id" />
                        </div>
                    </div>
                </div>

                {{-- Section: Password --}}
                <div>
                    <h3 class="mb-4 border-b border-slate-100 pb-3 text-lg font-bold text-slate-800">
                        كلمة المرور
                    </h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <x-form.label for="password">كلمة المرور</x-form.label>
                            <x-form.input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="8 أحرف على الأقل"
                                required
                            />
                            <x-form.error name="password" />
                        </div>

                        <div>
                            <x-form.label for="password_confirmation">تأكيد كلمة المرور</x-form.label>
                            <x-form.input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                placeholder="أعد إدخال كلمة المرور"
                                required
                            />
                        </div>
                    </div>
                </div>

                {{-- Section: Documents --}}
                <div>
                    <h3 class="mb-1 border-b border-slate-100 pb-3 text-lg font-bold text-slate-800">
                        المستندات المطلوبة
                    </h3>
                    <p class="mb-4 text-sm text-slate-500">
                        الصيغ المقبولة: PDF، JPG، PNG — الحجم الأقصى: 5 ميجابايت لكل ملف
                    </p>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <x-form.label for="bachelor_certificate">شهادة البكالوريوس (اختياري)</x-form.label>
                            <input
                                id="bachelor_certificate"
                                name="bachelor_certificate"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                            />
                            <x-form.error name="bachelor_certificate" />
                        </div>

                        <div>
                            <x-form.label for="remedial_statement">كشف المواد التكميلية (اختياري)</x-form.label>
                            <input
                                id="remedial_statement"
                                name="remedial_statement"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                            />
                            <x-form.error name="remedial_statement" />
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-between border-t border-slate-200 pt-6">
                    <p class="text-sm text-slate-500">
                        لديك حساب بالفعل؟
                        <a href="{{ url('/portal/login') }}" class="font-medium text-blue-600 hover:underline">تسجيل الدخول</a>
                    </p>
                    <x-form.button type="submit">
                        إرسال الطلب
                    </x-form.button>
                </div>
            </form>
        </div>
    </div>
</x-layout.app>
