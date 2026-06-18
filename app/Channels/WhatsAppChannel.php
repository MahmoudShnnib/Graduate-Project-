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
            // The send endpoint only QUEUES the message and returns status "pending".
            // We log that real status + Qonvo's message id; the actual sent/failed
            // outcome is filled in later by reconcilePendingLogs() (button + command).
            $result = $this->whatsApp->sendText($phone, $message);
            $data = $result['data'] ?? [];
            $status = WhatsAppService::mapStatus($data['status'] ?? null);
            $error = $status === 'failed' ? ($data['error_message'] ?? null) : null;
            $this->writeLog($studentId, $phone, $event, $messageText, $status, $error, $data['id'] ?? null);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp notification failed: '.$e->getMessage());
            $this->writeLog($studentId, $phone, $event, $messageText, 'failed', $e->getMessage());
        }
    }

    private function writeLog(?int $studentId, string $phone, string $event, string $message, string $status, ?string $errorMessage = null, int|string|null $providerMessageId = null): void
    {
        try {
            NotificationLog::create([
                'student_id' => $studentId,
                'channel' => 'whatsapp',
                'phone' => $phone,
                'event' => $event,
                'message' => $message,
                'status' => $status,
                'error_message' => $errorMessage,
                'provider_message_id' => $providerMessageId,
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never interrupt delivery
        }
    }
}
