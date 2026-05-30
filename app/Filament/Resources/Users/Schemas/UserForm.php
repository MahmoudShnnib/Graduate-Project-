<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                    ])
                    ->live(),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),

                // Faculty-only identity fields — mirrors the external-supervisor modal
                // on /admin/thesis-registrations so internal + external supervisors
                // share the same schema.
                Section::make('البيانات الشخصية')
                    ->icon(Heroicon::Identification)
                    ->columns(2)
                    ->visible(fn (callable $get): bool => $get('role') === 'faculty')
                    ->schema([
                        TextInput::make('national_id')
                            ->label('الرقم الوطني')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->native(false)
                            ->displayFormat('Y/m/d'),

                        TextInput::make('birth_place')
                            ->label('مكان الميلاد')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->maxLength(255),

                        TextInput::make('nationality')
                            ->label('الجنسية')
                            ->default('ليبية')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->maxLength(255),

                        Select::make('gender')
                            ->label('الجنس')
                            ->options([
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                            ])
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('البيانات الأكاديمية')
                    ->icon(Heroicon::AcademicCap)
                    ->description('تظهر هذه الحقول لأعضاء هيئة التدريس فقط، وتطابق نموذج المشرفين الخارجيين.')
                    ->columns(2)
                    ->visible(fn (callable $get): bool => $get('role') === 'faculty')
                    ->schema([
                        TextInput::make('academic_rank')
                            ->label('الدرجة الأكاديمية')
                            ->placeholder('مثال: أستاذ، أستاذ مشارك')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->maxLength(255),

                        TextInput::make('qualification')
                            ->label('المؤهل العلمي')
                            ->placeholder('مثال: دكتوراه')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->maxLength(255),

                        TextInput::make('institution')
                            ->label('جهة العمل / المؤسسة التعليمية')
                            ->default('كلية التقنية الإلكترونية – طرابلس')
                            ->required(fn (callable $get): bool => $get('role') === 'faculty')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
