<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('الاسم'),
                TextEntry::make('email')
                    ->label('البريد الإلكتروني'),
                TextEntry::make('phone')
                    ->label('الهاتف')
                    ->placeholder('-'),
                TextEntry::make('role')
                    ->label('الدور')
                    ->placeholder('-'),
                TextEntry::make('specialization.name')
                    ->label('التخصص')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
