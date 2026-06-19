<?php

namespace App\Filament\Widgets;

use App\Models\Semester;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class DashboardHeaderWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.dashboard-header';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{name: string, roleLabel: string, semesterName: string, canEditSettings: bool, applyOtpEnabled: bool}
     */
    public function getHeaderData(): array
    {
        $user = auth()->user();

        $roleLabels = [
            'super_admin' => 'مدير النظام',
            'manager_assistant' => 'مساعد إداري',
            'faculty' => 'عضو هيئة التدريس',
            'student' => 'طالب',
        ];

        return [
            'name' => $user?->name ?? '',
            'roleLabel' => $roleLabels[$user?->role] ?? '',
            'semesterName' => Semester::where('is_active', true)->value('name') ?? 'لا يوجد فصل نشط',
            'canEditSettings' => in_array($user?->role, ['super_admin', 'manager_assistant'], true),
            'applyOtpEnabled' => $this->applyOtpEnabled(),
        ];
    }

    private function applyOtpEnabled(): bool
    {
        return Setting::get('apply_otp_required', '1') === '1';
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
            ->fillForm(fn (): array => array_merge(
                Setting::allKeyed(),
                [
                    'apply_otp_required' => $this->applyOtpEnabled(),
                    'notification_channel_policy' => Setting::get('notification_channel_policy', 'both'),
                    'whatsapp_enabled' => Setting::get('whatsapp_enabled', '1') === '1',
                    'telegram_enabled' => Setting::get('telegram_enabled', '1') === '1',
                ],
            ))
            ->form([
                Section::make('قنوات الإشعارات')
                    ->description('تحدد كيف تُرسَل إشعارات الطلاب. يمكنك إغلاق أي قناة كلياً (مثلاً عند تعطّل واتساب).')
                    ->columns(2)
                    ->components([
                        Select::make('notification_channel_policy')
                            ->label('قناة الإرسال المفضّلة')
                            ->options([
                                'whatsapp_first' => 'واتساب أولاً (تيليجرام عند عدم توفر واتساب)',
                                'telegram_first' => 'تيليجرام أولاً (واتساب عند عدم توفر تيليجرام)',
                                'both' => 'كلاهما معاً (قد يصل الإشعار على التطبيقين)',
                            ])
                            ->default('both')
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->columnSpanFull(),

                        Toggle::make('whatsapp_enabled')
                            ->label('تفعيل قناة واتساب')
                            ->helperText('عند الإغلاق، يتوقف إرسال جميع الإشعارات والتحقق عبر واتساب.')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),

                        Toggle::make('telegram_enabled')
                            ->label('تفعيل قناة تيليجرام')
                            ->helperText('عند الإغلاق، يتوقف إرسال جميع الإشعارات والربط عبر تيليجرام.')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ]),

                Section::make('إعدادات التقديم')
                    ->description('تتحكم في صفحة التقديم العامة (/apply).')
                    ->components([
                        Toggle::make('apply_otp_required')
                            ->label('طلب رمز تحقق (OTP) من المتقدمين عبر واتساب')
                            ->helperText('عند الإيقاف، يستطيع المتقدمون إرسال الطلب دون التحقق من رقم هاتفهم عبر واتساب. استخدم هذا الخيار مؤقتاً فقط عند تعطل خدمة واتساب.')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(true),
                    ]),

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

                Section::make('بيانات التواصل')
                    ->description('تظهر في قسم "تواصل معنا" على صفحة التقديم العامة.')
                    ->columns(2)
                    ->components([
                        TextInput::make('contact_phone')
                            ->label('رقم الهاتف')
                            ->maxLength(255),

                        TextInput::make('contact_whatsapp')
                            ->label('رقم واتساب (دولي بدون +)')
                            ->helperText('مثال: 218910000000')
                            ->maxLength(255),

                        TextInput::make('contact_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('contact_hours')
                            ->label('ساعات العمل')
                            ->maxLength(255),

                        TextInput::make('contact_address')
                            ->label('العنوان')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data): void {
                foreach ($data as $key => $value) {
                    // Booleans (the OTP toggle) persist as '1'/'0'; everything else as-is.
                    Setting::set($key, match (true) {
                        is_bool($value) => $value ? '1' : '0',
                        is_string($value) => $value,
                        default => (string) $value,
                    });
                }

                Notification::make()
                    ->title('تم حفظ الإعدادات')
                    ->success()
                    ->send();
            });
    }
}
