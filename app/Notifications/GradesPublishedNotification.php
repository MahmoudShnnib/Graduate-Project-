<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single general "your grades are published" message sent to a student when an
 * admin publishes the term's final grades. It intentionally does NOT list each
 * course/grade — the student reviews the details in the portal.
 */
class GradesPublishedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'grades_published';

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            message: "عزيزي {$notifiable->name}، تم نشر درجاتك لهذا الفصل. يمكنك الاطلاع عليها من خلال بوابة الطالب.",
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
