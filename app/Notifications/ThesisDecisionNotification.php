<?php

namespace App\Notifications;

use App\Models\ThesisRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThesisDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ThesisRegistration $registration) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isApproved = $this->registration->status === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved ? 'قبول تسجيل الرسالة العلمية' : 'رفض تسجيل الرسالة العلمية')
            ->greeting('عزيزي الطالب،')
            ->line($isApproved
                ? 'يسعدنا إبلاغك بأنه تم **قبول** طلب تسجيل رسالتك العلمية.'
                : 'نأسف لإبلاغك بأنه تم **رفض** طلب تسجيل رسالتك العلمية.'
            )
            ->line('**عنوان الرسالة:** '.$this->registration->title);

        if ($isApproved) {
            $mail->line('يمكنك الآن المضي قدماً في إجراءات تسجيل الرسالة.');
        }

        return $mail
            ->action('عرض التفاصيل', url('/portal/thesis-registration-page'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'registration_id' => $this->registration->id,
            'status' => $this->registration->status,
        ];
    }
}
