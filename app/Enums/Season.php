<?php

namespace App\Enums;

enum Season: string
{
    case Autumn = 'autumn';
    case Spring = 'spring';
    case Summer = 'summer';

    public function label(): string
    {
        return match ($this) {
            self::Autumn => 'خريف',
            self::Spring => 'ربيع',
            self::Summer => 'صيف',
        };
    }
}
