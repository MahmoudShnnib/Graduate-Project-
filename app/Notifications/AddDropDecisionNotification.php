<?php

namespace App\Notifications;

use App\Models\AddDropRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddDropDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly AddDropRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isApproved = $this->request->status === 'approved';
        $typeLabel = $this->request->type?->label() ?? '—';
        $course = $this->request->courseOffering?->course;

        return (new MailMessage)
            ->subject("قرار طلب {$typeLabel} مقرر")
            ->greeting('عزيزي الطالب،')
            ->line($isApproved
                ? "تم **قبول** طلب {$typeLabel} المقرر: ".($course?->name ?? '—')
                : "تم **رفض** طلب {$typeLabel} المقرر: ".($course?->name ?? '—')
            )
            ->action('عرض الطلبات', url('/portal/add-drop-page'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'type' => $this->request->type?->value,
        ];
    }
}
