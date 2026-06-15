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
        public readonly ?string $plainPassword = null,
    ) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $enrollmentNo = $this->student->enrollment_no ?? '—';
        $text = "مرحباً {$notifiable->name}، تم قبولك رسمياً في برنامج الدراسات العليا.\nرقمك الجامعي: {$enrollmentNo}\nالبريد: {$notifiable->email}";

        if ($this->plainPassword) {
            $text .= "\nكلمة المرور: {$this->plainPassword}";
        }

        return new WhatsAppMessage(message: $text);
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
