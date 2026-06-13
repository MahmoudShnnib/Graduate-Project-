<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationExamPassedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'application_exam_passed';

    public function __construct(public readonly Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('نتيجة اختبار القبول — ناجح')
            ->greeting('عزيزي المتقدم،')
            ->line('يسرنا إبلاغك بأنك اجتزت اختبار القبول بنجاح.')
            ->line('اصبحت طالبا في كلية التقنية الالكترونية - الدراسات العليا.')
            ->action('الدخول إلى البوابة', url('/portal'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

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
