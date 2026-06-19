<?php

namespace App\Services;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Support\Str;

class TelegramVerificationService
{
    private const EXPIRES_MINUTES = 15;

    private const VERIFIED_GRACE_MINUTES = 30;

    private const CONTACT_BUTTON = '📱 مشاركة رقمي للتأكيد';

    public function __construct(private TelegramService $telegram) {}

    public function isConfigured(): bool
    {
        return $this->telegram->isEnabled() && ! empty($this->telegram->botUsername());
    }

    /**
     * Start (or reuse) a verification for a phone and return the row carrying the
     * deep-link token. Reusing a still-open row keeps the same link if the
     * applicant clicks twice, and keeps the table bounded.
     */
    public function start(string $phone, string $purpose = 'apply_phone'): TelegramVerification
    {
        $phone = TelegramService::toLocalLibyan($phone);

        $this->pruneExpired();

        $existing = TelegramVerification::query()
            ->where('intended_phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('status', '!=', 'mismatch')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return TelegramVerification::create([
            'token' => Str::random(32),
            'purpose' => $purpose,
            'intended_phone' => $phone,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);
    }

    /**
     * Start (or reuse) a student-portal link. Authenticated, so no contact-share is
     * needed — starting the bot from this token is enough to capture the chat id.
     */
    public function startForUser(User $user): TelegramVerification
    {
        $this->pruneExpired();

        $existing = TelegramVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', 'student_link')
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return TelegramVerification::create([
            'user_id' => $user->id,
            'token' => Str::random(32),
            'purpose' => 'student_link',
            'intended_phone' => TelegramService::toLocalLibyan((string) ($user->phone ?? '')),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);
    }

    public function isUserLinked(User $user): bool
    {
        return ! empty($user->telegram_chat_id);
    }

    public function unlinkUser(User $user): void
    {
        $user->forceFill(['telegram_chat_id' => null, 'telegram_linked_at' => null])->save();

        TelegramVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', 'student_link')
            ->delete();
    }

    public function deepLink(TelegramVerification $verification): string
    {
        return 'https://t.me/'.$this->telegram->botUsername().'?start='.$verification->token;
    }

    /**
     * Polled by the /apply page to learn whether the share-contact step completed.
     *
     * @return array{verified: bool, state: string, phone: ?string}
     */
    public function status(string $token): array
    {
        $verification = TelegramVerification::query()->where('token', $token)->first();

        if (! $verification) {
            return ['verified' => false, 'state' => 'unknown', 'phone' => null];
        }

        return [
            'verified' => $verification->isVerified(),
            'state' => $verification->status,
            'phone' => $verification->isVerified() ? $verification->telegram_phone : null,
        ];
    }

    /**
     * Whether a phone has a still-valid verified Telegram contact for a purpose.
     */
    public function hasVerified(string $phone, string $purpose = 'apply_phone'): bool
    {
        return $this->verifiedRow($phone, $purpose) !== null;
    }

    public function chatIdForVerifiedPhone(string $phone, string $purpose = 'apply_phone'): ?string
    {
        return $this->verifiedRow($phone, $purpose)?->chat_id;
    }

    /**
     * Handle one Telegram update from getUpdates: either a `/start <token>` (which
     * binds the chat and asks for the contact) or a shared contact (which verifies).
     *
     * @param  array<string, mixed>  $update
     */
    public function processUpdate(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $fromId = $message['from']['id'] ?? null;

        if ($chatId === null) {
            return;
        }

        if (isset($message['contact'])) {
            $this->handleContact($chatId, $fromId, $message['contact']);

            return;
        }

        $text = (string) ($message['text'] ?? '');

        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $fromId, trim(substr($text, strlen('/start'))));
        }
    }

    private function handleStart(int|string $chatId, int|string|null $fromId, string $token): void
    {
        if ($token === '') {
            $this->telegram->sendMessage($chatId, 'مرحباً بك. لإتمام التحقق، افتح رابط التحقق من صفحة التقديم.');

            return;
        }

        $verification = TelegramVerification::query()
            ->where('token', $token)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $verification) {
            $this->telegram->sendMessage($chatId, 'انتهت صلاحية رابط التحقق أو أنه غير صالح. يرجى العودة لصفحة التقديم وإعادة المحاولة.');

            return;
        }

        $verification->update([
            'chat_id' => (string) $chatId,
            'telegram_user_id' => $fromId !== null ? (string) $fromId : null,
        ]);

        // Student-portal linking is already authenticated — starting the bot from the
        // user's token is enough; capture the chat id and we're done (no contact step).
        if ($verification->purpose === 'student_link') {
            $verification->update(['status' => 'verified', 'verified_at' => now()]);

            if ($verification->user_id) {
                User::whereKey($verification->user_id)
                    ->update(['telegram_chat_id' => (string) $chatId, 'telegram_linked_at' => now()]);
            }

            $this->telegram->sendMessage(
                $chatId,
                '✅ تم ربط حسابك بنجاح. ستصلك إشعارات الدراسات العليا هنا على تيليجرام.',
                replyMarkup: ['remove_keyboard' => true],
            );

            return;
        }

        $verification->update(['status' => 'awaiting_contact']);

        $this->telegram->sendMessage(
            $chatId,
            'لتأكيد رقمك، اضغط زر «'.self::CONTACT_BUTTON."» بالأسفل.\nسيُشارك تطبيق تيليجرام رقمك الموثّق معنا — لن يُستخدم إلا للتحقق من طلبك.",
            replyMarkup: $this->telegram->requestContactKeyboard(self::CONTACT_BUTTON),
        );
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    private function handleContact(int|string $chatId, int|string|null $fromId, array $contact): void
    {
        $verification = TelegramVerification::query()
            ->where('chat_id', (string) $chatId)
            ->where('status', 'awaiting_contact')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $verification) {
            return;
        }

        // Anti-spoof: the request_contact button only ever shares the sender's OWN
        // number (contact.user_id === from.id). A forwarded/typed contact has a
        // different or absent user_id and must be rejected.
        $contactUserId = $contact['user_id'] ?? null;

        if ($contactUserId === null || (string) $contactUserId !== (string) $fromId) {
            $this->telegram->sendMessage($chatId, 'يرجى مشاركة رقمك أنت فقط عبر الزر، لا تمرّر جهة اتصال أخرى.');

            return;
        }

        $shared = TelegramService::toLocalLibyan((string) ($contact['phone_number'] ?? ''));
        $intended = TelegramService::toLocalLibyan($verification->intended_phone);

        if ($shared !== $intended) {
            $verification->update(['status' => 'mismatch', 'telegram_phone' => $shared]);

            $this->telegram->sendMessage(
                $chatId,
                "الرقم الذي شاركته ({$shared}) لا يطابق الرقم المُدخل في الطلب ({$intended}).\nتأكد من إدخال نفس رقمك في صفحة التقديم ثم أعد المحاولة.",
                replyMarkup: ['remove_keyboard' => true],
            );

            return;
        }

        $verification->update([
            'status' => 'verified',
            'telegram_phone' => $shared,
            'verified_at' => now(),
        ]);

        $this->telegram->sendMessage(
            $chatId,
            '✅ تم التحقق من رقمك بنجاح. يمكنك العودة لصفحة التقديم لإكمال إرسال طلبك.',
            replyMarkup: ['remove_keyboard' => true],
        );
    }

    private function verifiedRow(string $phone, string $purpose): ?TelegramVerification
    {
        $phone = TelegramService::toLocalLibyan($phone);

        return TelegramVerification::query()
            ->where('intended_phone', $phone)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(self::VERIFIED_GRACE_MINUTES))
            ->latest('verified_at')
            ->first();
    }

    private function pruneExpired(): void
    {
        TelegramVerification::query()
            ->where('expires_at', '<', now())
            ->whereNull('verified_at')
            ->delete();
    }
}
