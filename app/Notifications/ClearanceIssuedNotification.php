<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\ClearanceRequest;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClearanceIssuedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'clearance_issued';

    public function __construct(public readonly ClearanceRequest $clearance) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('اعتماد الاستماحة')
            ->greeting('عزيزي الطالب،')
            ->line('يسعدنا إبلاغك بأنه **تم اعتماد استماحتك** من مكتب الدراسات العليا.');

        if ($this->clearance->form_path) {
            $mail->action('تنزيل نموذج الاستماحة', asset('storage/'.$this->clearance->form_path));
        } else {
            $mail->action('عرض التفاصيل', url('/portal/clearance-page'));
        }

        return $mail->salutation('مع تحيات مكتب الدراسات العليا');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            message: 'عزيزي الطالب، تم اعتماد استماحتك من مكتب الدراسات العليا.',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'clearance_id' => $this->clearance->id,
        ];
    }
}
