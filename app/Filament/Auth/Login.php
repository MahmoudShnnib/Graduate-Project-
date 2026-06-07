<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Force a full-page redirect after login so the regenerated session cookie is
 * committed before the dashboard loads. SPA / wire:navigate can otherwise loop
 * back to the login form when the cookie is not yet applied.
 *
 * Also breaks wrong-panel redirect loops: when a student is already logged in
 * and visits /admin/login (or staff visits /portal/login), Filament would
 * bounce them to a panel they cannot access. We log out and send them to the
 * correct panel's login route instead.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $guard = Filament::auth();

        if ($guard->check()) {
            $user = $guard->user();

            if ($user instanceof FilamentUser && $user->canAccessPanel($panel)) {
                $this->redirectIntended(Filament::getUrl(), navigate: false);

                return;
            }

            if ($user instanceof FilamentUser) {
                $this->redirectToCorrectPanelLogin($user, $panel);

                return;
            }
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $response = parent::authenticate();
        } catch (ValidationException $exception) {
            if ($this->redirectToPreferredPanelAfterWrongPanelAttempt()) {
                return null;
            }

            throw $exception;
        }

        if ($response === null) {
            return null;
        }

        $this->redirectIntended(Filament::getUrl(), navigate: false);

        return null;
    }

    protected function redirectToCorrectPanelLogin(FilamentUser $user, Panel $currentPanel): void
    {
        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $preferredPanelId = $user instanceof User ? $user->preferredPanelId() : null;

        if ($preferredPanelId !== null && $preferredPanelId !== $currentPanel->getId()) {
            Notification::make()
                ->title('تم تسجيل الدخول من لوحة أخرى')
                ->body($preferredPanelId === 'student'
                    ? 'يُرجى تسجيل الدخول من بوابة الطالب.'
                    : 'يُرجى تسجيل الدخول من لوحة الإدارة.')
                ->warning()
                ->send();

            $this->redirect(Filament::getPanel($preferredPanelId)->getLoginUrl(), navigate: false);

            return;
        }

        $this->redirect($currentPanel->getLoginUrl(), navigate: false);
    }

    protected function redirectToPreferredPanelAfterWrongPanelAttempt(): bool
    {
        $data = $this->form->getState();
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user instanceof User || ! Hash::check($data['password'], $user->getAuthPassword())) {
            return false;
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if ($user->canAccessPanel($panel)) {
            return false;
        }

        $preferredPanelId = $user->preferredPanelId();

        if ($preferredPanelId === null || $preferredPanelId === $panel->getId()) {
            return false;
        }

        Notification::make()
            ->title('حسابك مرتبط بلوحة أخرى')
            ->body($preferredPanelId === 'student'
                ? 'يُرجى تسجيل الدخول من بوابة الطالب.'
                : 'يُرجى تسجيل الدخول من لوحة الإدارة.')
            ->warning()
            ->send();

        $this->redirect(Filament::getPanel($preferredPanelId)->getLoginUrl(), navigate: false);

        return true;
    }
}
