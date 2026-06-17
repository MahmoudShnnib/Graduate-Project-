<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Student;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeStudentNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public string $logEvent = 'welcome_student';

    public function __construct(
        public readonly Student $student,
    ) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $enrollmentNo = $this->student->enrollment_no ?? '—';

        return new WhatsAppMessage(
            message: "مرحباً {$notifiable->name}، يسرنا إبلاغك بأنك اجتزت اختبار القبول وتم قبولك رسمياً في برنامج الدراسات العليا.\n"
                ."رقمك الجامعي: {$enrollmentNo}\n"
                ."البريد: {$notifiable->email}",
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'enrollment_no' => $this->student->enrollment_no,
        ];
    }
}
