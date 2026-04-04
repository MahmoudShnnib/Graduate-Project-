<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'بريد إلكتروني',
            self::Whatsapp => 'واتساب',
        };
    }
}
