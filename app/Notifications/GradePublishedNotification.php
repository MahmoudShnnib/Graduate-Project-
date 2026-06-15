<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Grade;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradePublishedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'grade_published';

    public function __construct(public readonly Grade $grade) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $course = $this->grade->enrollment?->courseOffering?->course;

        return new WhatsAppMessage(
            message: 'عزيزي الطالب، تم اعتماد درجاتك في مقرر: '.($course?->name ?? '—')."\nالمجموع: ".number_format((float) $this->grade->total, 1)."\nالتقدير: ".($this->grade->letter ?? '—'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'grade_id' => $this->grade->id,
            'letter' => $this->grade->letter,
            'total' => $this->grade->total,
        ];
    }
}
