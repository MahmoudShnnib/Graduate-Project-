<?php

namespace App\Filament\Resources\SuspensionRequests\Pages;

use App\Enums\SuspensionStatus;
use App\Filament\Resources\SuspensionRequests\SuspensionRequestResource;
use App\Models\SuspensionRequest;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuspensionRequests extends ListRecords
{
    protected static string $resource = SuspensionRequestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn (): int => SuspensionRequest::query()->count()),

            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SuspensionStatus::Pending))
                ->badgeColor('warning')
                ->badge(fn (): int => SuspensionRequest::query()
                    ->where('status', SuspensionStatus::Pending)->count()),

            'approved' => Tab::make('مقبول')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SuspensionStatus::Approved))
                ->badgeColor('success')
                ->badge(fn (): int => SuspensionRequest::query()
                    ->where('status', SuspensionStatus::Approved)->count()),

            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SuspensionStatus::Rejected))
                ->badgeColor('danger')
                ->badge(fn (): int => SuspensionRequest::query()
                    ->where('status', SuspensionStatus::Rejected)->count()),
        ];
    }
}
