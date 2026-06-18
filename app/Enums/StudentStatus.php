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
    case Dismissed = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'مستمر',
            self::OnHold => 'قيده موقوف',
            self::Inactive => 'منقطع',
            self::Transferred => 'منتقل',
            self::Graduated => 'متخرج',
            self::Dismissed => 'مفصول',
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
            self::Dismissed => 'danger',
        };
    }

    /**
     * Whether this status freezes the GPA as a permanent snapshot. For these
     * statuses the cascade withdraws every enrollment but the GPA keeps its last
     * value, so the student's transcript + course tree must show their FULL record.
     *
     * The live statuses (Active, OnHold, Inactive) instead compute the GPA from
     * `activeEnrollments()` (Enrolled only) — so their views must show Enrolled
     * courses only, hiding any dropped/flushed (Withdrawn) course. This keeps the
     * visible set equal to the GPA-counted set for every status, including a
     * student reactivated from suspension back to Active.
     */
    public function freezesGpa(): bool
    {
        return match ($this) {
            self::Graduated, self::Transferred, self::Dismissed => true,
            self::Active, self::OnHold, self::Inactive => false,
        };
    }
}
