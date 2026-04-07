<?php

namespace App\Channels;

class WhatsAppMessage
{
    public function __construct(
        public readonly string $message,
        public readonly string $type = 'text',
    ) {}
}
