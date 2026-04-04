<?php

namespace App\Filament\Resources\CourseOfferings\Pages;

use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseOffering extends CreateRecord
{
    protected static string $resource = CourseOfferingResource::class;
}
