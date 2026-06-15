<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationReceivedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'application_received';

    public function __construct(public readonly Application $application) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            message: "مرحباً {$notifiable->name}، تم استلام طلب قبولك في برنامج الدراسات العليا بنجاح.\n\n"
                ."🔖 رقم التتبع الخاص بك: {$this->application->tracking_code}\n"
                ."احتفظ بهذا الرقم لمتابعة حالة طلبك.\n\n"
                .'لمتابعة حالة الطلب: '.route('application.track.show')."\n\n"
                .'سيتم مراجعة طلبك والتواصل معك قريباً.',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
        ];
    }
}
