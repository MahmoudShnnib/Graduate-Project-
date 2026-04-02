<?php

namespace App\Filament\Resources\Specializations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SpecializationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم التخصص')
                    ->required(),

                TextInput::make('code')
                    ->label('رمز التخصص')
                    ->required(),
            ]);
    }
}
