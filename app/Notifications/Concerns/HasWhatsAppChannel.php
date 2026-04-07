<?php

namespace App\Notifications\Concerns;

use App\Channels\WhatsAppChannel;

trait HasWhatsAppChannel
{
    /** @return array<int, string|class-string> */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (! empty($notifiable->phone) && ! empty(config('services.qonvo.api_token'))) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }
}
