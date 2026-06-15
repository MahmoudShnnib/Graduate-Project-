<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationExamPassedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'application_exam_passed';

    public function __construct(public readonly Application $application) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            message: "مرحباً {$notifiable->name}، يسرنا إبلاغك بأنك اجتزت اختبار القبول بنجاح. تم قبولك نهائياً في برنامج الدراسات العليا.",
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'status' => 'accepted',
        ];
    }
}
