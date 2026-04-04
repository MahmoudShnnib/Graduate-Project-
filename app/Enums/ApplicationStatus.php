<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case ProvisionallyAccepted = 'provisionally_accepted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::ProvisionallyAccepted => 'مقبول مبدئياً',
            self::Accepted => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }
}
