<?php

namespace App\Filament\Resources\Graduates\Pages;

use App\Filament\Resources\Graduates\GraduateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGraduates extends ListRecords
{
    protected static string $resource = GraduateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_clearance')
                ->label('طباعة نموذج الاستماحة')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn (): string => route('print.clearance-blank'))
                ->openUrlInNewTab(),
        ];
    }
}
