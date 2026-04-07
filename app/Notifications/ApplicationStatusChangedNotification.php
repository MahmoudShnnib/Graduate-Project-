<?php

namespace App\Notifications;

use App\Channels\WhatsAppMessage;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Notifications\Concerns\HasWhatsAppChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification
{
    use HasWhatsAppChannel, Queueable;

    public function __construct(public readonly Application $application) {}

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->application->status->label();

        $mail = (new MailMessage)
            ->subject('تحديث حالة طلب القبول')
            ->greeting('عزيزي المتقدم،')
            ->line("تم تحديث حالة طلب قبولك إلى: **{$label}**");

        if ($this->application->status === ApplicationStatus::InitiallyApproved && $this->application->exam_date) {
            $mail->line('موعد الاختبار: '.Carbon::parse($this->application->exam_date)->format('Y/m/d H:i'));
        }

        if ($this->application->rejection_reason) {
            $mail->line('سبب الرفض: '.$this->application->rejection_reason);
        }

        return $mail
            ->action('عرض الطلب', url('/portal'))
            ->salutation('مع تحيات مكتب الدراسات العليا');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $label = $this->application->status->label();
        $text = "مرحباً {$notifiable->name}، تم تحديث حالة طلب قبولك إلى: {$label}.";

        if ($this->application->status === ApplicationStatus::InitiallyApproved && $this->application->exam_date) {
            $text .= "\nموعد الاختبار: ".Carbon::parse($this->application->exam_date)->format('Y/m/d H:i');
        }

        if ($this->application->rejection_reason) {
            $text .= "\nسبب الرفض: {$this->application->rejection_reason}";
        }

        return new WhatsAppMessage(message: $text);
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
