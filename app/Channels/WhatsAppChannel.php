<?php

namespace App\Channels;

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

        try {
            $this->whatsApp->sendText($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp notification failed: '.$e->getMessage());
        }
    }
}
