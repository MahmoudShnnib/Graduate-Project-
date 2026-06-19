<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Channels\TelegramMessage;
use App\Channels\WhatsAppChannel;
use App\Channels\WhatsAppMessage;
use App\Notifications\Concerns\Batchable;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Security alert sent to the OLD phone (or current phone for an email-only
 * change) when a student's contact details are modified. Combined with the
 * phone-based OTP password reset, an unverified phone/email change is an
 * account-takeover vector — this notifies the likely victim so they can react.
 *
 * Sent on-demand (Notification::route) to a bare phone number and/or telegram
 * chat, so it does NOT use the channel traits (whose via() inspects model
 * attributes). via() here sends over whichever route is present + configured —
 * a security alert goes over BOTH channels for the best chance of reaching the
 * real owner, regardless of the notification_channel_policy.
 */
class ContactInfoChangedNotification extends Notification
{
    use Batchable, Queueable;

    public string $logEvent = 'contact_info_changed';

    /** @param  list<string>  $changedFields  Any of 'phone', 'email'. */
    public function __construct(public readonly array $changedFields) {}

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (app(WhatsAppService::class)->isEnabled() && $notifiable->routeNotificationFor('whatsapp')) {
            $channels[] = WhatsAppChannel::class;
        }

        if (app(TelegramService::class)->isEnabled() && $notifiable->routeNotificationFor('telegram')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    private function alertText(): string
    {
        $what = match (true) {
            in_array('phone', $this->changedFields, true) && in_array('email', $this->changedFields, true) => 'رقم الهاتف والبريد الإلكتروني',
            in_array('phone', $this->changedFields, true) => 'رقم الهاتف',
            default => 'البريد الإلكتروني',
        };

        return "تنبيه أمني: تم تغيير {$what} المرتبط بحسابك في بوابة الدراسات العليا. إذا لم تقم بهذا التغيير، يرجى التواصل مع مكتب الدراسات العليا فوراً.";
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(message: $this->alertText());
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return new TelegramMessage(message: $this->alertText());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['changed_fields' => $this->changedFields];
    }
}
