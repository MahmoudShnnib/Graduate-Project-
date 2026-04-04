<?php

namespace App\Notifications;

use App\Models\SuspensionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspensionDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SuspensionRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isApproved = $this->request->status?->value === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved ? 'قبول طلب الانقطاع' : 'رفض طلب الانقطاع')
            ->greeting('عزيزي الطالب،')
            ->line($isApproved
                ? 'تم **قبول** طلب انقطاعك للفصل الدراسي: '.($this->request->semester?->name ?? '—')
                : 'تم **رفض** طلب انقطاعك للفصل الدراسي: '.($this->request->semester?->name ?? '—')
            );

        if ($this->request->decision_notes) {
            $mail->line('ملاحظات الإدارة: '.$this->request->decision_notes);
        }

        return $mail
            ->action('عرض الطلبات', url('/portal/suspension-request-page'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'status' => $this->request->status?->value,
        ];
    }
}
