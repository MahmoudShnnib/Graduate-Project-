<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_number')
                    ->required(),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('specialization_id')
                    ->required()
                    ->numeric(),
                TextInput::make('admission_year')
                    ->required()
                    ->numeric(),
                TextInput::make('semester_type')
                    ->required()
                    ->numeric(),
            ]);
    }
}
