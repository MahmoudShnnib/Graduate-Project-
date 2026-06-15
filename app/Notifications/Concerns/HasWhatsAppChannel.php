<?php

namespace App\Notifications\Concerns;

use App\Channels\WhatsAppChannel;

trait HasWhatsAppChannel
{
    /**
     * Notifications are WhatsApp-only by design — email is out of scope.
     * When Qonvo is unconfigured or the notifiable has no phone, no channel
     * is returned (nothing is sent) rather than silently falling back to mail.
     *
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        if (! empty($notifiable->phone) && ! empty(config('services.qonvo.api_token'))) {
            return [WhatsAppChannel::class];
        }

        return [];
    }
}
