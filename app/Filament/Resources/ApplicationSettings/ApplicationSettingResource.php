<?php

namespace App\Filament\Resources\ApplicationSettings;

use App\Filament\Resources\ApplicationSettings\Pages\ManageApplicationSettings;
use App\Models\ApplicationSetting;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ApplicationSettingResource extends Resource
{
    protected static ?string $model = ApplicationSetting::class;

    protected static ?string $navigationLabel = 'إعدادات السنة والفصل';
    protected static ?string $modelLabel = 'إعدادات التسجيل';
    protected static ?string $pluralModelLabel = 'إعدادات النظام';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ضبط إعدادات النظام')
                    ->description('هذه القيم تؤثر على الأرقام الجامعية الجديدة للطلاب')
                    ->schema([
                        TextInput::make('current_year')
                            ->label('السنة الحالية')
                            ->required()
                            ->numeric(),

                        Select::make('active_semester')
                            ->label('الفصل الدراسي النشط')
                            ->options([
                                1 => 'خريف (Autumn)',
                                2 => 'ربيع (Spring)',
                            ])
                            ->required(),
                    ])

                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('current_year')
                    ->label('السنة')
                    ->badge(),

                TextColumn::make('active_semester')
                    ->label('الفصل')
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'خريف' : 'ربيع'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApplicationSettings::route('/'),
        ];
    }
}
