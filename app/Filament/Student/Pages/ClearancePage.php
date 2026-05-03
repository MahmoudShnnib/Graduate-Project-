<?php

namespace App\Filament\Student\Pages;

use App\Enums\DiscussionStatus;
use Filament\Pages\Page;

class ClearancePage extends Page
{
    protected string $view = 'filament.student.pages.clearance';

    protected static ?string $navigationLabel = 'الاستماحة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?int $navigationSort = 9;

    public function isDiscussionCompleted(): bool
    {
        $student = auth()->user()->student;

        if (! $student) {
            return false;
        }

        return $student->discussionRequests()
            ->where('status', DiscussionStatus::Completed)
            ->exists();
    }
}
