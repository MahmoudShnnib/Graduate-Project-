<?php

namespace App\Notifications;

use App\Channels\TelegramMessage;
use App\Channels\WhatsAppMessage;
use App\Notifications\Concerns\Batchable;
use App\Notifications\Concerns\HasMessagingChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single general "your grades are published" message sent to a student when an
 * admin publishes the term's final grades. It intentionally does NOT list each
 * course/grade — the student reviews the details in the portal.
 */
class GradesPublishedNotification extends Notification
{
    use Batchable, HasMessagingChannels, Queueable;

    public string $logEvent = 'grades_published';

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            message: "عزيزي {$notifiable->name}، تم نشر درجاتك لهذا الفصل. يمكنك الاطلاع عليها من خلال بوابة الطالب.",
        );
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return new TelegramMessage(
            message: "عزيزي {$notifiable->name}، تم نشر درجاتك لهذا الفصل. يمكنك الاطلاع عليها من خلال بوابة الطالب.",
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
