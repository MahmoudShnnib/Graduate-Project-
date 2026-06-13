<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationInitiallyApprovedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'application_initially_approved';

    public function __construct(public readonly Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('قبول مبدئي — موعد الامتحان التفاضلي')
            ->greeting("عزيزي {$notifiable->name}،")
            ->line('يسرّنا إبلاغك بأنه تم قبولك مبدئياً في برنامج الدراسات العليا، وعليك حضور الامتحان التفاضلي في الموعد التالي:');

        if ($this->application->exam_date) {
            $mail->line('📅 موعد الامتحان: '.$this->application->exam_date->format('Y/m/d - H:i'));
        }

        return $mail
            ->action('متابعة حالة الطلب', route('application.track.show'))
            ->line('نتمنى لك التوفيق.')
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $examLine = $this->application->exam_date
            ? '📅 موعد الامتحان: '.$this->application->exam_date->format('Y/m/d - H:i')."\n"
            : '';

        return new WhatsAppMessage(
            message: "عزيزي {$notifiable->name}، تم قبولك مبدئياً في برنامج الدراسات العليا وعليك حضور الامتحان التفاضلي.\n\n"
                .$examLine
                ."\nرابط متابعة الطلب: ".route('application.track.show')."\n\n"
                .'نتمنى لك التوفيق — مكتب الدراسات العليا.',
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
