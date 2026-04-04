<?php

namespace App\Notifications;

use App\Models\StudentEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly StudentEnrollment $enrollment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->enrollment->courseOffering?->course;
        $semester = $this->enrollment->courseOffering?->semester;

        return (new MailMessage)
            ->subject('تأكيد التسجيل في مقرر')
            ->greeting('عزيزي الطالب،')
            ->line('تم تسجيلك بنجاح في المقرر التالي:')
            ->line('**المقرر:** '.($course?->name ?? '—').' ('.($course?->code ?? '—').')')
            ->line('**الفصل الدراسي:** '.($semester?->name ?? '—'))
            ->line('**الساعات المعتمدة:** '.($course?->credits ?? '—'))
            ->action('عرض مقرراتي', url('/portal/student-courses-page'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->courseOffering?->course_id,
        ];
    }
}
