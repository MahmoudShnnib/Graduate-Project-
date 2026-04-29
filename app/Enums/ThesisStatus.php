<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ThesisStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Printed = 'printed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Printed => 'في انتظار الوزارة',
            self::Approved => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Printed => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function studentLabel(): string
    {
        return match ($this) {
            self::Pending, self::Printed => 'قيد المراجعة',
            self::Approved => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }

    public function studentColor(): string
    {
        return match ($this) {
            self::Pending, self::Printed => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
