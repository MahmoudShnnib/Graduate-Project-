<?php

namespace App\Enums;

enum CourseType: string
{
    case General = 'general';
    case Specialized = 'specialized';
    case Elective = 'elective';

    public function label(): string
    {
        return match ($this) {
            self::General => 'عام',
            self::Specialized => 'تخصصي',
            self::Elective => 'اختياري',
        };
    }
}
