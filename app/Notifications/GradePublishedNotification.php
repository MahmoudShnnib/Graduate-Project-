<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Grade;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePublishedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public function __construct(public readonly Grade $grade) {}

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->grade->enrollment?->courseOffering?->course;

        return (new MailMessage)
            ->subject('إعلان نتيجة مقرر')
            ->greeting('عزيزي الطالب،')
            ->line('تم اعتماد درجاتك في المقرر التالي:')
            ->line('**المقرر:** '.($course?->name ?? '—'))
            ->line('**المجموع:** '.number_format((float) $this->grade->total, 1).' / 100')
            ->line('**التقدير:** '.($this->grade->letter ?? '—'))
            ->action('عرض الدرجات', url('/portal/grades-page'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

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
