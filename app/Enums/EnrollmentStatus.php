<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Dropped = 'dropped';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'مسجل',
            self::Dropped => 'محذوف',
            self::Withdrawn => 'منسحب',
        };
    }
}
