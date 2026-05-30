<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class SettingsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.settings-widget';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, ['super_admin', 'secretary'], true);
    }

    public function editSettingsAction(): Action
    {
        return Action::make('editSettings')
            ->label('إعدادات النظام')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->size('sm')
            ->modalHeading('الإعدادات العامة')
            ->modalDescription('تظهر هذه القيم في رأس وتذييل المستندات المطبوعة.')
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('حفظ التغييرات')
            ->modalCancelActionLabel('إلغاء')
            ->fillForm(fn (): array => Setting::allKeyed())
            ->form([
                Section::make('بيانات الكلية')
                    ->columns(2)
                    ->components([
                        TextInput::make('college_name')
                            ->label('اسم الكلية')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('office_name')
                            ->label('اسم المكتب')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('التوقيعات الرسمية')
                    ->columns(2)
                    ->components([
                        TextInput::make('director_name')
                            ->label('مدير مكتب الدراسات العليا')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('dean_name')
                            ->label('عميد الكلية')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('director_name_en')
                            ->label('Director name (English)')
                            ->maxLength(255),

                        TextInput::make('dean_name_en')
                            ->label('Dean name (English)')
                            ->maxLength(255),
                    ]),
            ])
            ->action(function (array $data): void {
                foreach ($data as $key => $value) {
                    Setting::set($key, is_string($value) ? $value : (string) $value);
                }

                Notification::make()
                    ->title('تم حفظ الإعدادات')
                    ->success()
                    ->send();
            });
    }
}
