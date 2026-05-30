<?php

namespace App\Filament\Resources\DiscussionRequests\Pages;

use App\Enums\DiscussionStatus;
use App\Filament\Resources\DiscussionRequests\DiscussionRequestResource;
use App\Models\DiscussionRequest;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDiscussionRequests extends ListRecords
{
    protected static string $resource = DiscussionRequestResource::class;

    public function getTabs(): array
    {
        $approvedStates = [
            DiscussionStatus::Scheduled,
            DiscussionStatus::Discussed,
            DiscussionStatus::AcceptedWithEdits,
            DiscussionStatus::DiscussionApproved,
            DiscussionStatus::Completed,
        ];

        $rejectedStates = [
            DiscussionStatus::Rejected,
            DiscussionStatus::DiscussionRejected,
        ];

        return [
            'all' => Tab::make('الكل')
                ->badge(fn (): int => DiscussionRequest::query()->count()),

            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DiscussionStatus::Pending))
                ->badgeColor('warning')
                ->badge(fn (): int => DiscussionRequest::query()
                    ->where('status', DiscussionStatus::Pending)->count()),

            'approved' => Tab::make('مقبول')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $approvedStates))
                ->badgeColor('success')
                ->badge(fn (): int => DiscussionRequest::query()
                    ->whereIn('status', $approvedStates)->count()),

            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $rejectedStates))
                ->badgeColor('danger')
                ->badge(fn (): int => DiscussionRequest::query()
                    ->whereIn('status', $rejectedStates)->count()),
        ];
    }
}
