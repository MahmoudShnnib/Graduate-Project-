<?php

use App\Models\Application;
use App\Models\Specialization;
use App\Models\WhatsAppOtp;
use App\Support\SampleApplicantData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

function configureQonvo(bool $enabled = true): void
{
    config()->set('services.qonvo.api_token', $enabled ? 'test-token' : null);
    config()->set('services.qonvo.sender_phone', $enabled ? '0911111111' : null);
}

function validApplicationPayload(array $overrides = []): array
{
    Specialization::factory()->create();

    return array_merge(
        SampleApplicantData::forPublicApplication(),
        ['declaration' => '1'],
        $overrides,
    );
}

it('sends an OTP over WhatsApp and stores it for the phone', function () {
    configureQonvo();
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $this->postJson(route('registration.otp.send'), ['phone' => '0912345678'])
        ->assertSuccessful()
        ->assertJson(['sent' => true]);

    expect(WhatsAppOtp::where('phone', '0912345678')->where('purpose', 'apply_phone')->count())->toBeOne();
    Http::assertSent(fn ($request) => str_contains($request->url(), 'whatsapp/messages/send'));
});

it('rejects sending when phone is invalid', function () {
    configureQonvo();

    $this->postJson(route('registration.otp.send'), ['phone' => '12345'])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('phone');
});

it('enforces a resend cooldown', function () {
    configureQonvo();
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $this->postJson(route('registration.otp.send'), ['phone' => '0912345678'])->assertSuccessful();

    $this->postJson(route('registration.otp.send'), ['phone' => '0912345678'])
        ->assertStatus(422)
        ->assertJson(['sent' => false])
        ->assertJsonStructure(['retry_after']);
});

it('verifies a correct code and marks the OTP verified', function () {
    configureQonvo();

    $otp = WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson(route('registration.otp.verify'), ['phone' => '0912345678', 'code' => '123456'])
        ->assertSuccessful()
        ->assertJson(['verified' => true]);

    expect($otp->fresh()->verified_at)->not->toBeNull();
});

it('rejects a wrong code and counts the attempt', function () {
    configureQonvo();

    $otp = WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson(route('registration.otp.verify'), ['phone' => '0912345678', 'code' => '000000'])
        ->assertStatus(422)
        ->assertJson(['verified' => false]);

    expect($otp->fresh()->attempts)->toBeOne();
});

it('locks verification after too many wrong attempts', function () {
    configureQonvo();

    WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'attempts' => 5,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson(route('registration.otp.verify'), ['phone' => '0912345678', 'code' => '123456'])
        ->assertStatus(422)
        ->assertJson(['verified' => false]);
});

it('rejects an expired code', function () {
    configureQonvo();

    WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson(route('registration.otp.verify'), ['phone' => '0912345678', 'code' => '123456'])
        ->assertStatus(422)
        ->assertJson(['verified' => false]);
});

it('blocks application submission when the phone is not verified', function () {
    configureQonvo();

    $payload = validApplicationPayload(['phone' => '0915555555']);

    $this->from(route('registration.create'))
        ->post(route('registration.store'), $payload)
        ->assertRedirect(route('registration.create'))
        ->assertSessionHasErrors('phone');

    expect(Application::where('phone', '0915555555')->exists())->toBeFalse();
});

it('allows application submission when the phone is verified', function () {
    configureQonvo();

    $payload = validApplicationPayload(['phone' => '0915555555']);

    WhatsAppOtp::create([
        'phone' => '0915555555',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'verified_at' => now(),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->post(route('registration.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    expect(Application::where('phone', '0915555555')->exists())->toBeTrue();
});

it('allows application submission without OTP when WhatsApp is not configured', function () {
    configureQonvo(false);

    $payload = validApplicationPayload(['phone' => '0915555555']);

    $this->post(route('registration.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    expect(Application::where('phone', '0915555555')->exists())->toBeTrue();
});
