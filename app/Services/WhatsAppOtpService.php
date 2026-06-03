<?php

namespace App\Services;

use App\Channels\WhatsAppMessage;
use App\Models\WhatsAppOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpService
{
    private const CODE_LENGTH = 6;

    private const EXPIRES_MINUTES = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_SENDS_PER_WINDOW = 3;

    private const SEND_WINDOW_MINUTES = 15;

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * Generate and send a one-time code to the given phone for a purpose.
     *
     * @return array{sent: bool, message: string, retry_after?: int}
     */
    public function request(string $phone, string $purpose = 'apply_phone'): array
    {
        if (! $this->whatsApp->isConfigured()) {
            return ['sent' => false, 'message' => 'خدمة التحقق عبر واتساب غير مفعّلة حالياً.'];
        }

        $recent = WhatsAppOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
            ->latest('created_at')
            ->first();

        if ($recent) {
            $retryAfter = self::RESEND_COOLDOWN_SECONDS - $recent->created_at->diffInSeconds(now());

            return [
                'sent' => false,
                'message' => 'يرجى الانتظار قبل طلب رمز جديد.',
                'retry_after' => max(1, (int) $retryAfter),
            ];
        }

        $sendsInWindow = WhatsAppOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(self::SEND_WINDOW_MINUTES))
            ->count();

        if ($sendsInWindow >= self::MAX_SENDS_PER_WINDOW) {
            return [
                'sent' => false,
                'message' => 'تم تجاوز الحد المسموح من المحاولات. حاول مرة أخرى بعد قليل.',
            ];
        }

        $code = $this->generateCode();

        WhatsAppOtp::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        try {
            $this->whatsApp->sendText($phone, new WhatsAppMessage(
                message: "رمز التحقق الخاص بك في مكتب الدراسات العليا هو: {$code}\nصالح لمدة ".self::EXPIRES_MINUTES.' دقائق. لا تشاركه مع أحد.',
            ));
        } catch (\Throwable $e) {
            Log::warning('WhatsApp OTP send failed for purpose '.$purpose.': '.$e->getMessage());

            return ['sent' => false, 'message' => 'تعذّر إرسال رمز التحقق. يرجى المحاولة لاحقاً.'];
        }

        return ['sent' => true, 'message' => 'تم إرسال رمز التحقق عبر واتساب.'];
    }

    /**
     * Verify a submitted code against the latest active OTP for phone + purpose.
     *
     * @return array{verified: bool, message: string}
     */
    public function verify(string $phone, string $code, string $purpose = 'apply_phone'): array
    {
        $otp = WhatsAppOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('created_at')
            ->first();

        if (! $otp || $otp->isExpired()) {
            return ['verified' => false, 'message' => 'انتهت صلاحية الرمز أو لم يُطلب رمز بعد. اطلب رمزاً جديداً.'];
        }

        if ($otp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            return ['verified' => false, 'message' => 'تم تجاوز عدد المحاولات المسموح بها. اطلب رمزاً جديداً.'];
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return ['verified' => false, 'message' => 'الرمز غير صحيح.'];
        }

        $otp->forceFill(['verified_at' => now()])->save();

        return ['verified' => true, 'message' => 'تم التحقق من رقم الهاتف بنجاح.'];
    }

    /**
     * Whether a phone has a still-valid verified OTP for a purpose.
     */
    public function hasVerified(string $phone, string $purpose = 'apply_phone'): bool
    {
        return WhatsAppOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(30))
            ->exists();
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
