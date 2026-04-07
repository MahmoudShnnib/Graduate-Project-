<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case InitiallyApproved = 'initially_approved';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::InitiallyApproved => 'مقبول مبدئياً',
            self::Accepted => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }
}
