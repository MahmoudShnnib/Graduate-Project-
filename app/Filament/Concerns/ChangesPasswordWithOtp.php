<?php

namespace App\Filament\Concerns;

use App\Services\WhatsAppOtpService;
use Filament\Notifications\Notification;

/**
 * Shared behaviour for the logged-in "change password" page in both panels.
 *
 * The user must confirm a WhatsApp OTP sent to the phone on file before the new
 * password is saved. Used by the thin ChangePassword page in each panel.
 * See _brain/project-context/password-management-guide.md.
 */
trait ChangesPasswordWithOtp
{
    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $otp_code = '';

    public bool $otpSent = false;

    private const OTP_PURPOSE = 'password_change';

    public function phone(): ?string
    {
        return auth()->user()?->phone;
    }

    public function hasPhone(): bool
    {
        return filled($this->phone());
    }

    /**
     * Send a verification code to the user's registered phone.
     */
    public function sendOtp(): void
    {
        $phone = $this->phone();

        if (blank($phone)) {
            Notification::make()
                ->title('لا يوجد رقم هاتف مسجل لحسابك. يرجى التواصل مع الإدارة.')
                ->danger()
                ->send();

            return;
        }

        $result = app(WhatsAppOtpService::class)->request($phone, self::OTP_PURPOSE);
        $this->otpSent = $result['sent'];

        Notification::make()
            ->title($result['message'])
            ->{$result['sent'] ? 'success' : 'danger'}()
            ->send();
    }

    /**
     * Validate the current password + OTP, then persist the new password.
     */
    public function updatePassword(): void
    {
        $this->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            'otp_code' => ['required'],
        ], [
            'new_password.required' => 'يرجى إدخال كلمة المرور الجديدة',
            'new_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'new_password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
            'otp_code.required' => 'يرجى إدخال رمز التحقق',
        ]);

        $user = auth()->user();

        $phone = $this->phone();

        if (blank($phone)) {
            Notification::make()->title('لا يوجد رقم هاتف مسجل لحسابك.')->danger()->send();

            return;
        }

        $result = app(WhatsAppOtpService::class)->verify($phone, $this->otp_code, self::OTP_PURPOSE);

        if (! $result['verified']) {
            $this->addError('otp_code', $result['message']);

            return;
        }

        // 'password' is cast to 'hashed' on the User model — assigning the plain value hashes it.
        $user->update(['password' => $this->new_password]);

        $this->reset(['new_password', 'new_password_confirmation', 'otp_code']);
        $this->otpSent = false;

        Notification::make()->title('تم تغيير كلمة المرور بنجاح.')->success()->send();
    }
}
