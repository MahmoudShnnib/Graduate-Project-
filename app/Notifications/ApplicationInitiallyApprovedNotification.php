<?php

namespace App\Notifications;

use App\Channels\TelegramMessage;
use App\Channels\WhatsAppMessage;
use App\Models\Application;
use App\Models\Setting;
use App\Notifications\Concerns\Batchable;
use App\Notifications\Concerns\HasMessagingChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationInitiallyApprovedNotification extends Notification
{
    use Batchable, HasMessagingChannels, Queueable;

    public string $logEvent = 'application_initially_approved';

    public function __construct(public readonly Application $application) {}

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $examLine = $this->application->exam_date
            ? '📅 موعد الامتحان: '.$this->application->exam_date->format('Y/m/d - H:i')."\n"
            : '';

        $collegeName = Setting::get('college_name');

        return new WhatsAppMessage(
            message: "عزيزي {$notifiable->name}، تم قبولك مبدئياً في برنامج الدراسات العليا في {$collegeName} وعليك حضور الامتحان التفاضلي.\n\n"
                .$examLine
                ."\nرابط متابعة الطلب: ".route('application.track.show')."\n\n"
                .'نتمنى لك التوفيق — مكتب الدراسات العليا.',
        );
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $examLine = $this->application->exam_date
            ? '📅 موعد الامتحان: '.$this->application->exam_date->format('Y/m/d - H:i')."\n"
            : '';

        $collegeName = Setting::get('college_name');

        return new TelegramMessage(
            message: "عزيزي {$notifiable->name}، تم قبولك مبدئياً في برنامج الدراسات العليا في {$collegeName} وعليك حضور الامتحان التفاضلي.\n\n"
                .$examLine
                ."\nرابط متابعة الطلب: ".route('application.track.show')."\n\n"
                .'نتمنى لك التوفيق — مكتب الدراسات العليا.',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
        ];
    }
}
