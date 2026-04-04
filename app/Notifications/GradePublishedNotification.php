<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Grade $grade) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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
