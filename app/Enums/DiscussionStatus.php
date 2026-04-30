<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiscussionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Scheduled = 'scheduled';
    case Discussed = 'discussed';
    case DiscussionRejected = 'discussion_rejected';
    case AcceptedWithEdits = 'accepted_with_edits';
    case DiscussionApproved = 'discussion_approved';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Rejected => 'مرفوض',
            self::Scheduled => 'تم تحديد موعد المناقشة',
            self::Discussed => 'تمت المناقشة',
            self::DiscussionRejected => 'مرفوض بعد المناقشة',
            self::AcceptedWithEdits => 'بانتظار التعديلات',
            self::DiscussionApproved => 'تمت الموافقة على المناقشة',
            self::Completed => 'تم التجليد والتسليم',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Rejected, self::DiscussionRejected => 'danger',
            self::Scheduled, self::Discussed => 'info',
            self::AcceptedWithEdits => 'warning',
            self::DiscussionApproved => 'success',
            self::Completed => 'success',
        };
    }

    public function studentLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Rejected, self::DiscussionRejected => 'مرفوض',
            self::Scheduled, self::Discussed, self::AcceptedWithEdits => 'مقبول',
            self::DiscussionApproved, self::Completed => 'تمت الموافقة النهائية',
        };
    }

    public function studentColor(): string
    {
        return match ($this) {
            self::Pending, self::AcceptedWithEdits => 'warning',
            self::Rejected, self::DiscussionRejected => 'danger',
            self::Scheduled, self::Discussed => 'success',
            self::DiscussionApproved, self::Completed => 'success',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Rejected, self::DiscussionRejected, self::Completed]);
    }
}
