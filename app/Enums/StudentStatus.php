<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Suspended => 'موقوف',
            self::Withdrawn => 'منسحب',
            self::Graduated => 'متخرج',
        };
    }
}
