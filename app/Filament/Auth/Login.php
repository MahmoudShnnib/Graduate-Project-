<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

/**
 * Force a full-page redirect after login so the regenerated session cookie is
 * committed before the dashboard loads. SPA / wire:navigate can otherwise loop
 * back to the login form when the cookie is not yet applied.
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response === null) {
            return null;
        }

        $this->redirectIntended(Filament::getUrl(), navigate: false);

        return null;
    }
}
