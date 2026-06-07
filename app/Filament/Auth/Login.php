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
 * Wrong-panel guard: students on /admin/login (or staff on /portal/login) are
 * logged out and sent to the correct panel — breaks dual-panel redirect loops.
 *
 * Successful auth must return Filament's LoginResponse (bound in AppServiceProvider).
 * Returning null after login makes Livewire re-render the form — looks like a refresh.
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
            return parent::authenticate();
        } catch (ValidationException $exception) {
            if ($this->redirectToPreferredPanelAfterWrongPanelAttempt()) {
                return null;
            }

            throw $exception;
        }
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
