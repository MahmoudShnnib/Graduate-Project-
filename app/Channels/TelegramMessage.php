<?php

namespace App\Channels;

class TelegramMessage
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $parseMode = null,
    ) {}
}
