<?php

namespace App\Channels;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function __construct(private TelegramService $telegram) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->telegram->isConfigured()) {
            return;
        }

        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $chatId = $notifiable->routeNotificationFor('telegram') ?? $notifiable->telegram_chat_id ?? null;

        if (! $chatId) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (! $message instanceof TelegramMessage) {
            return;
        }

        $studentId = $notifiable instanceof User ? $notifiable->student?->id : null;
        $event = $notification->logEvent ?? class_basename($notification);
        $phone = $notifiable->phone ?? (string) $chatId;

        try {
            // Telegram reports delivery synchronously, so a successful send is
            // logged 'sent' immediately — no pending/reconcile step like WhatsApp.
            $result = $this->telegram->sendText($chatId, $message);
            $providerMessageId = $result['result']['message_id'] ?? null;
            $this->writeLog($studentId, $phone, $event, $message->message, 'sent', null, $providerMessageId);
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed: '.$e->getMessage());
            $this->writeLog($studentId, $phone, $event, $message->message, 'failed', $e->getMessage());
        }
    }

    private function writeLog(?int $studentId, string $phone, string $event, string $message, string $status, ?string $errorMessage = null, int|string|null $providerMessageId = null): void
    {
        try {
            NotificationLog::create([
                'student_id' => $studentId,
                'channel' => 'telegram',
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
