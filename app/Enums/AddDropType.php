<?php

namespace App\Enums;

enum AddDropType: string
{
    case Add = 'add';
    case Drop = 'drop';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'إضافة',
            self::Drop => 'حذف',
        };
    }
}
