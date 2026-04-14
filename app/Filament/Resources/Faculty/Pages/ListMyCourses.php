<?php

namespace App\Filament\Resources\Faculty\Pages;

use App\Filament\Resources\Faculty\MyCourseResource;
use Filament\Resources\Pages\ListRecords;

class ListMyCourses extends ListRecords
{
    protected static string $resource = MyCourseResource::class;

    public function getTitle(): string
    {
        return 'موادي المكلف بها';
    }
}
