<x-filament-panels::page>
    @if (! $this->hasPhone())
        <x-filament::section>
            <p class="font-semibold text-danger-600">
                لا يوجد رقم هاتف مسجل لحسابك، لذلك لا يمكن تغيير كلمة المرور عبر رمز التحقق على واتساب.
                يرجى التواصل مع إدارة الدراسات العليا لإضافة رقم هاتف.
            </p>
        </x-filament::section>
    @else
        <form wire:submit="updatePassword" class="max-w-lg space-y-6">
            <x-filament::section>
                <x-slot name="heading">تغيير كلمة المرور</x-slot>
                <x-slot name="description">
                    سيتم إرسال رمز تحقق إلى رقم هاتفك المسجّل على واتساب لتأكيد العملية.
                </x-slot>

                <div class="space-y-4">
                    {{-- New password --}}
                    <div class="space-y-1">
                        <label for="new_password" class="text-sm font-medium text-gray-950 dark:text-white">كلمة المرور الجديدة</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="password" id="new_password" wire:model="new_password" />
                        </x-filament::input.wrapper>
                        @error('new_password')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirm new password --}}
                    <div class="space-y-1">
                        <label for="new_password_confirmation" class="text-sm font-medium text-gray-950 dark:text-white">تأكيد كلمة المرور الجديدة</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="password" id="new_password_confirmation" wire:model="new_password_confirmation" />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Send OTP --}}
                    <div class="flex items-center gap-3">
                        <x-filament::button type="button" color="gray" icon="heroicon-o-chat-bubble-left-right" wire:click="sendOtp">
                            إرسال رمز التحقق عبر واتساب
                        </x-filament::button>
                        @if ($otpSent)
                            <span class="text-sm font-semibold text-success-600">تم إرسال الرمز ✓</span>
                        @endif
                    </div>

                    {{-- OTP code (revealed after send) --}}
                    @if ($otpSent)
                        <div class="space-y-1">
                            <label for="otp_code" class="text-sm font-medium text-gray-950 dark:text-white">رمز التحقق</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" id="otp_code" inputmode="numeric" maxlength="6" wire:model="otp_code" />
                            </x-filament::input.wrapper>
                            @error('otp_code')
                                <p class="text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::button
                type="submit"
                icon="heroicon-o-key"
                :disabled="! $otpSent"
                wire:confirm="هل أنت متأكد من تغيير كلمة المرور؟"
            >
                تغيير كلمة المرور
            </x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
