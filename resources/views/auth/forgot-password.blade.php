<x-layout.app title="إعادة تعيين كلمة المرور">
    @php
        $isAdmin = ($mode ?? 'student') === 'admin';
        $identifierField = $isAdmin ? 'email' : 'national_id';
    @endphp
    <div class="mx-auto max-w-xl">

        {{-- Header --}}
        <div class="mb-8 text-center">
            <span class="inline-flex rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                استعادة الحساب
            </span>
            <h2 class="mt-4 text-3xl font-extrabold text-slate-900">إعادة تعيين كلمة المرور</h2>
            <p class="mt-3 text-base text-slate-500">
                @if ($isAdmin)
                    أدخل بريدك الإلكتروني وسنرسل رمز تحقق عبر واتساب إلى رقم الهاتف المسجّل في حسابك.
                @else
                    أدخل رقمك الوطني وسنرسل رمز تحقق عبر واتساب إلى رقم الهاتف المسجّل في حسابك.
                @endif
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Step 1: identify by the panel-appropriate key + send OTP --}}
            <div class="space-y-2">
                <label for="identifier" class="block text-sm font-bold text-slate-700">{{ $isAdmin ? 'البريد الإلكتروني' : 'الرقم الوطني' }}</label>
                <input type="{{ $isAdmin ? 'email' : 'text' }}" id="identifier" name="{{ $identifierField }}"
                       inputmode="{{ $isAdmin ? 'email' : 'numeric' }}" autocomplete="off" dir="{{ $isAdmin ? 'ltr' : 'rtl' }}"
                       value="{{ old($identifierField) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error($identifierField)
                    <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror

                <button type="button" id="otp-send-btn"
                        class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-800 disabled:opacity-60">
                    <span id="otp-send-label">إرسال رمز التحقق عبر واتساب</span>
                </button>
                <p id="otp-status" class="text-xs text-slate-500"></p>
            </div>

            {{-- Step 2: code + new password (revealed after OTP sent) --}}
            <form method="POST" action="{{ route('password.reset') }}" id="reset-form" class="mt-6 hidden space-y-4 border-t border-slate-100 pt-6">
                @csrf
                <input type="hidden" name="{{ $identifierField }}" id="reset-identifier">

                <p class="text-sm text-slate-600">
                    تم إرسال رمز التحقق إلى الرقم <span id="phone-masked" class="font-bold" dir="ltr"></span>
                </p>

                <div class="space-y-1">
                    <label for="code" class="block text-sm font-bold text-slate-700">رمز التحقق</label>
                    <input type="text" name="code" id="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 tracking-widest focus:border-blue-500 focus:ring-blue-500">
                    @error('code')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label for="password" class="block text-sm font-bold text-slate-700">كلمة المرور الجديدة</label>
                    <input type="password" name="password" id="password" autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    @error('password')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label for="password_confirmation" class="block text-sm font-bold text-slate-700">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow hover:bg-emerald-700">
                    إعادة تعيين كلمة المرور
                </button>
            </form>

            <div class="mt-6 text-center text-sm">
                <a href="{{ route('registration.create') }}" class="font-semibold text-blue-700 hover:underline">العودة إلى الصفحة الرئيسية</a>
            </div>
        </div>
    </div>

    @php($hasErrors = $errors->has('code') || $errors->has('password'))

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const identifierKey = @json($identifierField);
            const emptyMessage = @json($isAdmin ? 'يرجى إدخال البريد الإلكتروني أولاً.' : 'يرجى إدخال الرقم الوطني أولاً.');
            const csrf = document.querySelector('#reset-form input[name="_token"]')?.value;
            const identifierInput = document.getElementById('identifier');
            const sendBtn = document.getElementById('otp-send-btn');
            const sendLabel = document.getElementById('otp-send-label');
            const status = document.getElementById('otp-status');
            const form = document.getElementById('reset-form');
            const resetIdentifier = document.getElementById('reset-identifier');
            const phoneMasked = document.getElementById('phone-masked');
            let cooldownTimer = null;

            const setStatus = (msg, ok = false) => {
                status.textContent = msg;
                status.className = 'text-xs ' + (ok ? 'text-emerald-700' : 'text-red-600');
            };

            const revealForm = (masked, identifier) => {
                phoneMasked.textContent = masked ?? '';
                resetIdentifier.value = identifier;
                form.classList.remove('hidden');
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

            sendBtn.addEventListener('click', async () => {
                const identifier = identifierInput.value.trim();
                if (! identifier) {
                    setStatus(emptyMessage);
                    return;
                }
                sendBtn.disabled = true;
                setStatus('جاري الإرسال...');
                try {
                    const res = await fetch('{{ route('password.otp.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ [identifierKey]: identifier }),
                    });
                    const data = await res.json().catch(() => ({}));
                    setStatus(data.message ?? (res.ok ? 'تم الإرسال.' : 'تعذّر الإرسال.'), res.ok);
                    if (res.ok) {
                        revealForm(data.phone_masked, identifier);
                        startCooldown(120);
                    } else {
                        sendBtn.disabled = false;
                        if (data.retry_after) startCooldown(data.retry_after);
                    }
                } catch (e) {
                    sendBtn.disabled = false;
                    setStatus('حدث خطأ في الاتصال. حاول مرة أخرى.');
                }
            });

            // If the server bounced back validation errors on the reset step, keep the form open.
            @if ($hasErrors)
                resetIdentifier.value = identifierInput.value = @json(old($identifierField));
                form.classList.remove('hidden');
            @endif
        });
    </script>
</x-layout.app>
