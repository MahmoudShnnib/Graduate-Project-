<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\ClearanceRequest;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClearanceIssuedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'clearance_issued';

    public function __construct(public readonly ClearanceRequest $clearance) {}

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
