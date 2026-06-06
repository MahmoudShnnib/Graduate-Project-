<?php

use App\Models\User;
use App\Models\WhatsAppOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/*
 * Logged-in change-password page (WhatsApp OTP only), shared by both
 * panels via App\Filament\Concerns\ChangesPasswordWithOtp.
 * See _brain/project-context/password-management-guide.md.
 */

function passwordChangeOtp(string $phone, string $code): void
{
    WhatsAppOtp::create([
        'phone' => $phone,
        'purpose' => 'password_change',
        'code_hash' => Hash::make($code),
        'expires_at' => now()->addMinutes(5),
    ]);
}

it('changes the password with a valid otp', function () {
    $user = User::factory()->withRole('super_admin')->create(['phone' => '0912345678']);
    actingAs($user);
    passwordChangeOtp('0912345678', '123456');

    Livewire::test(\App\Filament\Pages\ChangePassword::class)
        ->set('new_password', 'newpass123')
        ->set('new_password_confirmation', 'newpass123')
        ->set('otpSent', true)
        ->set('otp_code', '123456')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid otp code', function () {
    $user = User::factory()->withRole('secretary')->create(['phone' => '0912345678']);
    actingAs($user);
    passwordChangeOtp('0912345678', '123456');

    Livewire::test(\App\Filament\Pages\ChangePassword::class)
        ->set('new_password', 'newpass123')
        ->set('new_password_confirmation', 'newpass123')
        ->set('otpSent', true)
        ->set('otp_code', '000000')
        ->call('updatePassword')
        ->assertHasErrors('otp_code');
});

it('sends a password_change otp to the registered phone', function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '+218911111111');
    Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);

    $user = User::factory()->withRole('secretary')->create(['phone' => '0912345678']);
    actingAs($user);

    Livewire::test(\App\Filament\Pages\ChangePassword::class)
        ->call('sendOtp')
        ->assertSet('otpSent', true);

    expect(WhatsAppOtp::where('phone', '0912345678')->where('purpose', 'password_change')->exists())->toBeTrue();
});

it('does not send an otp when the account has no phone', function () {
    $user = User::factory()->withRole('secretary')->create(['phone' => null]);
    actingAs($user);

    Livewire::test(\App\Filament\Pages\ChangePassword::class)
        ->call('sendOtp')
        ->assertSet('otpSent', false);

    expect($this->app['view']->exists('filament.pages.change-password'))->toBeTrue();
});

it('lets a student change their password via the student-panel page', function () {
    ['user' => $user] = studentWithRecord();
    $user->update(['phone' => '0912345678']);
    actingAs($user);
    passwordChangeOtp('0912345678', '654321');

    Livewire::test(\App\Filament\Student\Pages\ChangePassword::class)
        ->set('new_password', 'newpass123')
        ->set('new_password_confirmation', 'newpass123')
        ->set('otpSent', true)
        ->set('otp_code', '654321')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeTrue();
});
