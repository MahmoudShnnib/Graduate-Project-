<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Inactive = 'inactive';
    case Transferred = 'transferred';
    case Graduated = 'graduated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'مستمر',
            self::OnHold => 'قيده موقوف',
            self::Inactive => 'منقطع',
            self::Transferred => 'منتقل',
            self::Graduated => 'متخرج',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::OnHold => 'danger',
            self::Inactive => 'warning',
            self::Transferred => 'info',
            self::Graduated => 'info',
        };
    }
}
