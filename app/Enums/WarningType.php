<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WarningType: string implements HasLabel
{
    case CourseFailure = 'course_failure';
    case ConsecutiveInactive = 'consecutive_inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::CourseFailure => 'رسوب في مادة',
            self::ConsecutiveInactive => 'انقطاع متتالي',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
