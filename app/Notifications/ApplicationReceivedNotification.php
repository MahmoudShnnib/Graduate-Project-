<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceivedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'application_received';

    public function __construct(public readonly Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('استلام طلب القبول')
            ->greeting('عزيزي المتقدم،')
            ->line('تم استلام طلب قبولك في برنامج الدراسات العليا بنجاح.')
            ->line('سيتم مراجعة طلبك من قِبَل لجنة القبول والتواصل معك في أقرب وقت ممكن.')
            ->action('متابعة الطلب', url('/portal'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

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
