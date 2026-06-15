<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\ThesisRegistration;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ThesisDecisionNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'thesis_decision';

    public function __construct(public readonly ThesisRegistration $registration) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $isApproved = $this->registration->status === 'approved';
        $decision = $isApproved ? 'قبول' : 'رفض';

        return new WhatsAppMessage(
            message: "عزيزي الطالب، تم {$decision} طلب تسجيل رسالتك العلمية: {$this->registration->title}",
        );
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
