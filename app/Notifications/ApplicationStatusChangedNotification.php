<?php

namespace App\Notifications;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Application $application) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'pending' => 'قيد المراجعة',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
            'exam_scheduled' => 'تم تحديد موعد الاختبار',
        ];

        $label = $statusLabels[$this->application->status] ?? $this->application->status;

        $mail = (new MailMessage)
            ->subject('تحديث حالة طلب القبول')
            ->greeting('عزيزي المتقدم،')
            ->line("تم تحديث حالة طلب قبولك إلى: **{$label}**");

        if ($this->application->status === 'exam_scheduled' && $this->application->exam_date) {
            $mail->line('موعد الاختبار: '.Carbon::parse($this->application->exam_date)->format('Y/m/d H:i'));
        }

        if ($this->application->rejection_reason) {
            $mail->line('سبب الرفض: '.$this->application->rejection_reason);
        }

        return $mail
            ->action('عرض الطلب', url('/portal'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'status' => $this->application->status,
        ];
    }
}
