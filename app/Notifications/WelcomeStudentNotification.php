<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Models\Student;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeStudentNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public function __construct(
        public readonly Student $student,
        public readonly ?string $plainPassword = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('مرحباً بك في برنامج الدراسات العليا')
            ->greeting('عزيزي الطالب '.$notifiable->name.'،')
            ->line('يسعدنا إبلاغك بأنه تم قبولك رسمياً في برنامج الدراسات العليا.')
            ->line('**رقمك الجامعي:** '.($this->student->enrollment_no ?? '—'))
            ->line('**البريد الإلكتروني:** '.$notifiable->email);

        if ($this->plainPassword) {
            $mail->line('**كلمة المرور:** '.$this->plainPassword);
            $mail->line('يرجى تغيير كلمة المرور بعد أول تسجيل دخول.');
        }

        return $mail
            ->action('الدخول إلى بوابة الطالب', url('/portal'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

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
