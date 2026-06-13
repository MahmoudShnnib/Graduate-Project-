<?php

namespace App\Channels;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->whatsApp->isConfigured()) {
            return;
        }

        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp') ?? $notifiable->phone ?? null;

        if (! $phone) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (! $message instanceof WhatsAppMessage) {
            return;
        }

        $studentId = $notifiable instanceof User ? $notifiable->student?->id : null;
        $event = $notification->logEvent ?? class_basename($notification);
        $messageText = $message->message;

        try {
            $this->whatsApp->sendText($phone, $message);
            $this->writeLog($studentId, $event, $messageText, 'sent');
        } catch (\Throwable $e) {
            Log::warning('WhatsApp notification failed: '.$e->getMessage());
            $this->writeLog($studentId, $event, $messageText, 'failed', $e->getMessage());
        }
    }

    private function writeLog(?int $studentId, string $event, string $message, string $status, ?string $errorMessage = null): void
    {
        try {
            NotificationLog::create([
                'student_id' => $studentId,
                'channel' => 'whatsapp',
                'event' => $event,
                'message' => $message,
                'status' => $status,
                'error_message' => $errorMessage,
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never interrupt delivery
        }
    }
}
