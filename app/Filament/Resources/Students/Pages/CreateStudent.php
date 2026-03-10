<?php

namespace App\Filament\Resources\Students\Pages;

use App\Actions\GenerateStudentNumberAction;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Specialization;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['student_number'] = (new GenerateStudentNumberAction)(
            Specialization::find($data['specialization_id']),
            $data['semester_type']
        );

        return $data;
    }
}
