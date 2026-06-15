<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Grade;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseworkGradePublishedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'coursework_grade_published';

    public function __construct(public readonly Grade $grade) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $offering = $this->grade->enrollment?->courseOffering;
        $course = $offering?->course;

        return new WhatsAppMessage(
            message: 'عزيزي الطالب، تم نشر درجة أعمال الفصل في مقرر: '.($course?->name ?? '—')."\nأعمال الفصل: ".number_format((float) $this->grade->coursework_grade, 1).' / '.($offering?->coursework_max ?? '—'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'grade_id' => $this->grade->id,
            'coursework_grade' => $this->grade->coursework_grade,
        ];
    }
}
