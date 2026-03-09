<x-layout.app title="التسجيل للدراسات العليا">
    <div class="mx-auto max-w-5xl">
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

        @if(session('success'))
            <div class="mb-8 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-700 font-bold">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
            <form method="POST" action="{{ route('registration.store') }}" class="space-y-8">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <x-form.label for="full_name">الاسم الكامل</x-form.label>
                        <x-form.input
                            id="full_name"
                            name="full_name"
                            type="text"
                            value="{{ old('full_name') }}"
                            required
                        />
                        <x-form.error name="full_name" />
                    </div>

                    <div>
                        <x-form.label for="email">البريد الإلكتروني</x-form.label>
                        <x-form.input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                        />
                        <x-form.error name="email" />
                    </div>

                    <div>
                        <x-form.label for="phone">رقم الهاتف</x-form.label>
                        <x-form.input
                            id="phone"
                            name="phone"
                            type="text"
                            value="{{ old('phone') }}"
                            required
                        />
                        <x-form.error name="phone" />
                    </div>

                    <div>
                        <x-form.label for="specialization_id">التخصص</x-form.label>
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

                    <div>
                        <x-form.label for="semester_type">نوع الفصل</x-form.label>
                        <x-form.select id="semester_type" name="semester_type" required>
                            <option value="">اختر نوع الفصل</option>
                            <option value="1" @selected(old('semester_type') == '1')>خريف</option>
                            <option value="2" @selected(old('semester_type') == '2')>ربيع</option>
                            <option value="3" @selected(old('semester_type') == '3')>خريف منتقل</option>
                            <option value="4" @selected(old('semester_type') == '4')>ربيع منتقل</option>
                        </x-form.select>
                        <x-form.error name="semester_type" />
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <x-form.button type="submit">
                        إرسال الطلب
                    </x-form.button>
                </div>
            </form>
        </div>
    </div>
</x-layout.app>
