<?php

namespace App\Filament\Resources\DiscussionRequests\Pages;

use App\Filament\Resources\DiscussionRequests\DiscussionRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDiscussionRequests extends ListRecords
{
    protected static string $resource = DiscussionRequestResource::class;
}
