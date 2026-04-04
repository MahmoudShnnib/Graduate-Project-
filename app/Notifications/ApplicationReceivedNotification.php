<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Application $application) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
        ];
    }
}
