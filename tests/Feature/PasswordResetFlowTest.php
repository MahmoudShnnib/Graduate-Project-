<?php

use App\Models\User;
use App\Models\WhatsAppOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/*
 * Public WhatsApp-OTP forgot-password flow.
 * See _brain/project-context/password-management-guide.md.
 */

function passwordResetOtp(string $phone, string $code): WhatsAppOtp
{
    return WhatsAppOtp::create([
        'phone' => $phone,
        'purpose' => 'password_reset',
        'code_hash' => Hash::make($code),
        'expires_at' => now()->addMinutes(5),
    ]);
}

it('renders the forgot-password page', function () {
    $this->get(route('password.forgot'))
        ->assertOk()
        ->assertSee('إعادة تعيين كلمة المرور');
});

it('sends an otp to the phone on file when the national id matches', function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '+218911111111');
    Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);

    User::factory()->create(['national_id' => '123456789012', 'phone' => '0912345678']);

    $this->postJson(route('password.otp.send'), ['national_id' => '123456789012'])
        ->assertOk()
        ->assertJsonPath('sent', true)
        ->assertJsonPath('phone_masked', '09••••5678');

    expect(WhatsAppOtp::where('phone', '0912345678')->where('purpose', 'password_reset')->exists())->toBeTrue();
});

it('rejects an unknown national id', function () {
    $this->postJson(route('password.otp.send'), ['national_id' => '000000000000'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد حساب بهذا الرقم الوطني.');
});

it('blocks reset when the account has no phone on file', function () {
    User::factory()->create(['national_id' => '123456789012', 'phone' => null]);

    $this->postJson(route('password.otp.send'), ['national_id' => '123456789012'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد رقم هاتف مسجل لهذا الحساب. يرجى التواصل مع إدارة الدراسات العليا.');
});

it('resets the password with a valid code and redirects a student to the portal login', function () {
    $user = User::factory()->create([
        'national_id' => '123456789012',
        'phone' => '0912345678',
        'role' => 'student',
    ]);
    passwordResetOtp('0912345678', '123456');

    $this->post(route('password.reset'), [
        'national_id' => '123456789012',
        'code' => '123456',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertRedirect('/portal/login');

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeTrue();
});

it('redirects a non-student to the admin login after reset', function () {
    User::factory()->create([
        'national_id' => '999999999999',
        'phone' => '0913333333',
        'role' => 'secretary',
    ]);
    passwordResetOtp('0913333333', '654321');

    $this->post(route('password.reset'), [
        'national_id' => '999999999999',
        'code' => '654321',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertRedirect('/admin/login');
});

it('rejects reset with a wrong code and leaves the password unchanged', function () {
    $user = User::factory()->create([
        'national_id' => '123456789012',
        'phone' => '0912345678',
        'password' => 'original-pass',
    ]);
    passwordResetOtp('0912345678', '123456');

    $this->from(route('password.forgot'))
        ->post(route('password.reset'), [
            'national_id' => '123456789012',
            'code' => '000000',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])
        ->assertRedirect(route('password.forgot'))
        ->assertSessionHasErrors('code');

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeFalse();
});

it('rejects a mismatched password confirmation', function () {
    User::factory()->create(['national_id' => '123456789012', 'phone' => '0912345678']);
    passwordResetOtp('0912345678', '123456');

    $this->from(route('password.forgot'))
        ->post(route('password.reset'), [
            'national_id' => '123456789012',
            'code' => '123456',
            'password' => 'newpass123',
            'password_confirmation' => 'different456',
        ])
        ->assertSessionHasErrors('password');
});
