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

    public bool $otpVerified = false;

    private const OTP_PURPOSE = 'password_change';

    /**
     * Real-time verification: the moment a full 6-digit code is entered, check it
     * against the active OTP and flip {@see $otpVerified} so the UI can show a
     * "correct" indicator and enable the submit button — mirroring the public
     * /apply auto-verify behaviour.
     */
    public function updatedOtpCode(): void
    {
        $this->otp_code = preg_replace('/\D/', '', (string) $this->otp_code) ?? '';

        if ($this->otpVerified || strlen($this->otp_code) !== 6) {
            return;
        }

        $phone = $this->phone();

        if (blank($phone)) {
            return;
        }

        $result = app(WhatsAppOtpService::class)->verify($phone, $this->otp_code, self::OTP_PURPOSE);
        $this->otpVerified = $result['verified'];

        if ($this->otpVerified) {
            $this->resetErrorBag('otp_code');
        } else {
            $this->addError('otp_code', $result['message']);
        }
    }

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

        if ($result['sent']) {
            $this->otp_code = '';
            $this->otpVerified = false;
            $this->resetErrorBag('otp_code');
        }

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

        $otpService = app(WhatsAppOtpService::class);

        // The code may already be verified in real time (updatedOtpCode). If not,
        // fall back to verifying the submitted code here so the page still works
        // without live verification.
        if (! $otpService->hasVerified($phone, self::OTP_PURPOSE)) {
            $result = $otpService->verify($phone, $this->otp_code, self::OTP_PURPOSE);

            if (! $result['verified']) {
                $this->addError('otp_code', $result['message']);

                return;
            }
        }

        // 'password' is cast to 'hashed' on the User model — assigning the plain value hashes it.
        $user->update(['password' => $this->new_password]);

        $this->reset(['new_password', 'new_password_confirmation', 'otp_code']);
        $this->otpSent = false;
        $this->otpVerified = false;

        Notification::make()->title('تم تغيير كلمة المرور بنجاح.')->success()->send();
    }
}
