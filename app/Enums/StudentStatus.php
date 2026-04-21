<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Inactive => 'القيد موقوف مؤقتًا',
            self::Withdrawn => 'منسحب',
            self::Graduated => 'متخرج',
        };
    }
}
