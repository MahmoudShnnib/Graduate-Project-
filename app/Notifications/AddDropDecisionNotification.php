<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\AddDropRequest;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AddDropDecisionNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'add_drop_decision';

    public function __construct(public readonly AddDropRequest $request) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $isApproved = $this->request->status === 'approved';
        $decision = $isApproved ? 'قبول' : 'رفض';
        $typeLabel = $this->request->type?->label() ?? '—';
        $course = $this->request->courseOffering?->course;

        return new WhatsAppMessage(
            message: "عزيزي الطالب، تم {$decision} طلب {$typeLabel} المقرر: ".($course?->name ?? '—'),
        );
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
