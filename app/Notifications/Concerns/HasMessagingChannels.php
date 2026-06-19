<?php

namespace App\Notifications\Concerns;

use App\Channels\TelegramChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Setting;
use App\Services\TelegramService;
use App\Services\WhatsAppService;

/**
 * Routes a notification over WhatsApp and/or Telegram based on the admin-configured
 * `notification_channel_policy`, whether each channel is enabled (not closed by the
 * admin), and what the notifiable can actually receive. Only notifications that
 * implement both `toWhatsApp()` and `toTelegram()` should use this.
 */
trait HasMessagingChannels
{
    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        $whatsapp = ! empty($notifiable->phone) && app(WhatsAppService::class)->isEnabled();
        $telegram = ! empty($notifiable->telegram_chat_id) && app(TelegramService::class)->isEnabled();

        $channels = match (Setting::get('notification_channel_policy', 'both')) {
            'both' => array_filter([
                $whatsapp ? WhatsAppChannel::class : null,
                $telegram ? TelegramChannel::class : null,
            ]),
            'telegram_first' => $telegram ? [TelegramChannel::class] : ($whatsapp ? [WhatsAppChannel::class] : []),
            default => $whatsapp ? [WhatsAppChannel::class] : ($telegram ? [TelegramChannel::class] : []),
        };

        return array_values($channels);
    }
}
