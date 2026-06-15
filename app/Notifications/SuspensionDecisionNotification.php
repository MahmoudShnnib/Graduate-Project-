<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\SuspensionRequest;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuspensionDecisionNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'suspension_decision';

    public function __construct(public readonly SuspensionRequest $request) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $isApproved = $this->request->status?->value === 'approved';
        $decision = $isApproved ? 'قبول' : 'رفض';
        $semester = $this->request->semester?->name ?? '—';

        return new WhatsAppMessage(
            message: "عزيزي الطالب، تم {$decision} طلب انقطاعك للفصل الدراسي: {$semester}",
        );
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
