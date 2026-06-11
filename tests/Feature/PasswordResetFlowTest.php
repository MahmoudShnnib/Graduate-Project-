<?php

use App\Models\User;
use App\Models\WhatsAppOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/*
 * Public WhatsApp-OTP forgot-password flow. Two entry points:
 *   - students (/portal/forgot-password) identify by national ID (role = student);
 *   - staff   (/admin/forgot-password)  identify by email       (role != student).
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

it('renders the student forgot-password page with a national-id field', function () {
    $this->get(route('password.forgot.student'))
        ->assertOk()
        ->assertSee('الرقم الوطني');
});

it('renders the admin forgot-password page with an email field', function () {
    $this->get(route('password.forgot.admin'))
        ->assertOk()
        ->assertSee('البريد الإلكتروني');
});

it('sends an otp to the phone on file when a student national id matches', function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '+218911111111');
    Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);

    User::factory()->withRole('student')->create(['national_id' => '123456789012', 'phone' => '0912345678']);

    $this->postJson(route('password.otp.send'), ['national_id' => '123456789012'])
        ->assertOk()
        ->assertJsonPath('sent', true)
        ->assertJsonPath('phone_masked', '09••••5678');

    expect(WhatsAppOtp::where('phone', '0912345678')->where('purpose', 'password_reset')->exists())->toBeTrue();
});

it('sends an otp to the phone on file when a staff email matches', function () {
    config()->set('services.qonvo.api_token', 'test-token');
    config()->set('services.qonvo.sender_phone', '+218911111111');
    Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);

    User::factory()->withRole('secretary')->create(['email' => 'sec@college.edu', 'phone' => '0913333333']);

    $this->postJson(route('password.otp.send'), ['email' => 'sec@college.edu'])
        ->assertOk()
        ->assertJsonPath('sent', true);

    expect(WhatsAppOtp::where('phone', '0913333333')->where('purpose', 'password_reset')->exists())->toBeTrue();
});

it('rejects an unknown student national id', function () {
    $this->postJson(route('password.otp.send'), ['national_id' => '000000000000'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد حساب طالب بهذا الرقم الوطني.');
});

it('rejects an unknown staff email', function () {
    $this->postJson(route('password.otp.send'), ['email' => 'nobody@college.edu'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد حساب موظّف بهذا البريد الإلكتروني.');
});

it('does not resolve a student through the staff email path', function () {
    User::factory()->withRole('student')->create(['email' => 'student@college.edu', 'phone' => '0912345678']);

    $this->postJson(route('password.otp.send'), ['email' => 'student@college.edu'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد حساب موظّف بهذا البريد الإلكتروني.');
});

it('does not resolve a staff member through the student national-id path', function () {
    User::factory()->faculty()->create(['national_id' => '111111111111', 'phone' => '0914444444']);

    $this->postJson(route('password.otp.send'), ['national_id' => '111111111111'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد حساب طالب بهذا الرقم الوطني.');
});

it('blocks reset when the matched account has no phone on file', function () {
    User::factory()->withRole('student')->create(['national_id' => '123456789012', 'phone' => null]);

    $this->postJson(route('password.otp.send'), ['national_id' => '123456789012'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'لا يوجد رقم هاتف مسجل لهذا الحساب. يرجى التواصل مع إدارة الدراسات العليا.');
});

it('resets a student password with a valid code and redirects to the portal login', function () {
    $user = User::factory()->withRole('student')->create([
        'national_id' => '123456789012',
        'phone' => '0912345678',
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

it('resets a staff password by email and redirects to the admin login', function () {
    $user = User::factory()->withRole('secretary')->create([
        'email' => 'sec@college.edu',
        'phone' => '0913333333',
    ]);
    passwordResetOtp('0913333333', '654321');

    $this->post(route('password.reset'), [
        'email' => 'sec@college.edu',
        'code' => '654321',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertRedirect('/admin/login');

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeTrue();
});

it('rejects reset with a wrong code and leaves the password unchanged', function () {
    $user = User::factory()->withRole('student')->create([
        'national_id' => '123456789012',
        'phone' => '0912345678',
        'password' => 'original-pass',
    ]);
    passwordResetOtp('0912345678', '123456');

    $this->from(route('password.forgot.student'))
        ->post(route('password.reset'), [
            'national_id' => '123456789012',
            'code' => '000000',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])
        ->assertRedirect(route('password.forgot.student'))
        ->assertSessionHasErrors('code');

    expect(Hash::check('newpass123', $user->fresh()->password))->toBeFalse();
});

it('rejects a mismatched password confirmation', function () {
    User::factory()->withRole('student')->create(['national_id' => '123456789012', 'phone' => '0912345678']);
    passwordResetOtp('0912345678', '123456');

    $this->from(route('password.forgot.student'))
        ->post(route('password.reset'), [
            'national_id' => '123456789012',
            'code' => '123456',
            'password' => 'newpass123',
            'password_confirmation' => 'different456',
        ])
        ->assertSessionHasErrors('password');
});
