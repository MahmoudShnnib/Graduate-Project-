<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Channels\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Security alert sent to the OLD phone (or current phone for an email-only
 * change) when a student's contact details are modified. Combined with the
 * phone-based OTP password reset, an unverified phone/email change is an
 * account-takeover vector — this notifies the likely victim so they can react.
 *
 * Sent on-demand (Notification::route) to a bare phone number, so it does NOT
 * use HasWhatsAppChannel (whose via() inspects $notifiable->phone). via() here
 * only checks Qonvo config; the channel resolves the routed number itself.
 */
class ContactInfoChangedNotification extends Notification
{
    use Queueable;

    public string $logEvent = 'contact_info_changed';

    /** @param  list<string>  $changedFields  Any of 'phone', 'email'. */
    public function __construct(public readonly array $changedFields) {}

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        return ! empty(config('services.qonvo.api_token')) ? [WhatsAppChannel::class] : [];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $what = match (true) {
            in_array('phone', $this->changedFields, true) && in_array('email', $this->changedFields, true) => 'رقم الهاتف والبريد الإلكتروني',
            in_array('phone', $this->changedFields, true) => 'رقم الهاتف',
            default => 'البريد الإلكتروني',
        };

        return new WhatsAppMessage(
            message: "تنبيه أمني: تم تغيير {$what} المرتبط بحسابك في بوابة الدراسات العليا. إذا لم تقم بهذا التغيير، يرجى التواصل مع مكتب الدراسات العليا فوراً.",
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['changed_fields' => $this->changedFields];
    }
}
