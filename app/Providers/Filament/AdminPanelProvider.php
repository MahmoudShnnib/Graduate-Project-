<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('إدارة الدراسات العليا')
            ->spa()
            ->spaUrlExceptions([
                '*/admin/login',
                '*/admin/login/*',
            ])
            ->login(\App\Filament\Auth\Login::class)
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('تغيير كلمة المرور')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => \App\Filament\Pages\ChangePassword::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => '<div style="margin-top:1rem;text-align:center;"><a href="'.e(route('password.forgot.admin')).'" style="font-size:.875rem;font-weight:600;color:#1F4E79;">نسيت كلمة المرور؟</a></div>',
            )
            ->colors([
                'primary' => Color::hex('#1F4E79'),
            ])
            ->navigationGroups([
                'القبول والتسجيل',
                'شؤون الطلاب',
                'المناقشة والتخرج',
                'الإعدادات الأكاديمية',
                'إدارة الوصول والنظام',
                'هيئة التدريس',
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn (): string => view('filament.nav-alerts')->render(),
            )
            ->font('Noto Kufi Arabic', url: asset('css/noto-kufi-arabic.css'), provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            // Register our RoleResource explicitly BEFORE the Shield plugin so
            // Shield's `isResourcePublished()` check sees a `\RoleResource`
            // class already in the panel and skips registering its own
            // (vendor) RoleResource. Audit fix M-4.
            ->resources([
                RoleResource::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
