<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required(),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->required()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                TextInput::make('phone')
                    ->label('رقم الهاتف'),

                Select::make('role')
                    ->label('الدور')
                    ->options([
                        'super_admin' => 'رئيس مكتب الدراسات العليا',
                        'secretary' => 'السكرتير',
                        'faculty' => 'عضو هيئة التدريس',
                        'student' => 'الطالب',
                    ]),

                Select::make('specialization_id')
                    ->relationship('specialization', 'name')
                    ->label('التخصص')
                    ->searchable()
                    ->preload(),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }
}
