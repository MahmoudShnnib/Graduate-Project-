<?php

namespace App\Notifications;

use App\Channels\TelegramMessage;
use App\Channels\WhatsAppMessage;
use App\Models\Setting;
use App\Models\Student;
use App\Notifications\Concerns\Batchable;
use App\Notifications\Concerns\HasMessagingChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeStudentNotification extends Notification
{
    use Batchable, HasMessagingChannels, Queueable;

    public string $logEvent = 'welcome_student';

    public function __construct(
        public readonly Student $student,
    ) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $enrollmentNo = $this->student->enrollment_no ?? '—';
        $collegeName = Setting::get('college_name');

        return new WhatsAppMessage(
            message: "مرحباً {$notifiable->name}، يسرنا إبلاغك بأنك اجتزت اختبار القبول وتم قبولك رسمياً في برنامج الدراسات العليا في {$collegeName}.\n"
                ."رقمك الجامعي: {$enrollmentNo}\n"
                ."البريد: {$notifiable->email}",
        );
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $enrollmentNo = $this->student->enrollment_no ?? '—';
        $collegeName = Setting::get('college_name');

        return new TelegramMessage(
            message: "مرحباً {$notifiable->name}، يسرنا إبلاغك بأنك اجتزت اختبار القبول وتم قبولك رسمياً في برنامج الدراسات العليا في {$collegeName}.\n"
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
