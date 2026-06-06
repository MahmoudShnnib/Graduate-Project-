<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChangesPasswordWithOtp;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Admin-panel "change password" page (super_admin / secretary / faculty).
 * Reached from the user menu; logic lives in {@see ChangesPasswordWithOtp}.
 */
class ChangePassword extends Page
{
    use ChangesPasswordWithOtp;

    protected string $view = 'filament.pages.change-password';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Key;

    protected static ?string $title = 'تغيير كلمة المرور';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
