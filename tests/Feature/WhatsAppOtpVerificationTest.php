<?php

use App\Models\Application;
use App\Models\Specialization;
use App\Models\WhatsAppOtp;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    // The Setting static memo survives RefreshDatabase, so a value set in one test
    // would leak into the next — reset it for deterministic apply_otp_required reads.
    \App\Models\Setting::flushCache();
    // An open active semester so the admissions window gate lets submissions through.
    \App\Models\Semester::factory()->active()->create();
});

function configureQonvo(bool $enabled = true): void
{
    config()->set('services.qonvo.api_token', $enabled ? 'test-token' : null);
    config()->set('services.qonvo.sender_phone', $enabled ? '0911111111' : null);
}

function validApplicationPayload(array $overrides = []): array
{
    $specialization = Specialization::factory()->create();

    return array_merge([
        'name' => 'محمد أحمد علي الزاوي',
        'name_en' => 'Mohammed Ahmed Ali Zawai',
        'email' => 'applicant_'.uniqid().'@example.com',
        'phone' => '0912345678',
        'specialization_id' => $specialization->id,
        'mother_name' => 'فاطمة المبروك',
        'birth_date' => '1990-01-15',
        'birth_place' => 'طرابلس',
        'gender' => 'male',
        'marital_status' => 'single',
        'nationality' => 'ليبيا',
        'national_id' => '119900115'.str_pad((string) rand(1, 999), 3, '0', STR_PAD_LEFT),
        'card_number' => '12345',
        'residence' => 'طرابلس',
        'permanent_address' => 'طرابلس شارع الجمهورية',
        'study_type' => 'self_funded',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'declaration' => '1',
        'bachelor_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
    ], $overrides);
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

it('degrades gracefully when the WhatsApp provider times out', function () {
    configureQonvo();
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $this->postJson(route('registration.otp.send'), ['phone' => '0912345678'])
        ->assertStatus(422)
        ->assertJson(['sent' => false]);
});

it('prunes fully-dead OTP rows when a new code is requested', function () {
    configureQonvo();
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $dead = WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('111111'),
        'expires_at' => now()->subMinutes(15),
    ]);
    $dead->forceFill(['created_at' => now()->subMinutes(20)])->save();

    app(WhatsAppOtpService::class)->request('0912345678');

    expect(WhatsAppOtp::find($dead->id))->toBeNull();
    expect(WhatsAppOtp::where('phone', '0912345678')->count())->toBe(1);
});

it('does not prune a verified OTP still inside the grace window', function () {
    configureQonvo();
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $verified = WhatsAppOtp::create([
        'phone' => '0912345678',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('111111'),
        'verified_at' => now()->subMinutes(10),
        'expires_at' => now()->subMinutes(10),
    ]);
    $verified->forceFill(['created_at' => now()->subMinutes(20)])->save();

    $service = app(WhatsAppOtpService::class);
    $service->request('0912345678');

    expect(WhatsAppOtp::find($verified->id))->not->toBeNull();
    expect($service->hasVerified('0912345678'))->toBeTrue();
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

it('reports OTP required only when configured AND the apply_otp_required setting is on', function () {
    $svc = app(WhatsAppOtpService::class);

    configureQonvo(false);
    expect($svc->isRequired())->toBeFalse();

    configureQonvo(true);
    App\Models\Setting::set('apply_otp_required', '1');
    expect($svc->isRequired())->toBeTrue();

    App\Models\Setting::set('apply_otp_required', '0');
    expect($svc->isRequired())->toBeFalse();
});

it('allows submission without OTP when the admin disables the requirement (WhatsApp still configured)', function () {
    configureQonvo();
    App\Models\Setting::set('apply_otp_required', '0');

    $payload = validApplicationPayload(['phone' => '0915555555']);

    $this->post(route('registration.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    expect(Application::where('phone', '0915555555')->exists())->toBeTrue();
});

it('the settings popup toggle persists apply_otp_required', function () {
    $this->actingAs(adminUser());

    expect(App\Models\Setting::get('apply_otp_required', '1'))->toBe('1');

    Livewire\Livewire::test(App\Filament\Widgets\DashboardHeaderWidget::class)
        ->callAction('editSettings', data: [
            'college_name' => 'كلية التقنية الإلكترونية',
            'office_name' => 'مكتب الدراسات العليا',
            'director_name' => 'مدير',
            'dean_name' => 'عميد',
            'apply_otp_required' => false,
        ])
        ->assertHasNoActionErrors();

    expect(App\Models\Setting::get('apply_otp_required', '1'))->toBe('0');
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

it('keeps the phone marked verified on the apply page after a failed submit', function () {
    configureQonvo();

    WhatsAppOtp::create([
        'phone' => '0915555555',
        'purpose' => 'apply_phone',
        'code_hash' => Hash::make('123456'),
        'verified_at' => now()->subMinutes(10),
        'expires_at' => now()->subMinutes(10),
    ]);

    // Mimics back()->withInput() repopulating old('phone') after a validation failure.
    $this->withSession(['_old_input' => ['phone' => '0915555555']])
        ->get(route('registration.create'))
        ->assertOk()
        ->assertSee('let verified = true', false);
});

it('does not mark an unverified phone as verified on the apply page', function () {
    configureQonvo();

    $this->withSession(['_old_input' => ['phone' => '0915555555']])
        ->get(route('registration.create'))
        ->assertOk()
        ->assertSee('let verified = false', false);
});

it('allows application submission without OTP when WhatsApp is not configured', function () {
    configureQonvo(false);

    $payload = validApplicationPayload(['phone' => '0915555555']);

    $this->post(route('registration.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    expect(Application::where('phone', '0915555555')->exists())->toBeTrue();
});

it('stores uploaded certificate files on application submission', function () {
    configureQonvo(false);
    Storage::fake('public');

    $payload = validApplicationPayload(['phone' => '0916666666']);
    $payload['bachelor_certificate'] = UploadedFile::fake()->create('certificate.pdf', 512, 'application/pdf');
    $payload['remedial_statement'] = UploadedFile::fake()->image('remedial.jpg');

    $this->post(route('registration.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    $application = Application::where('phone', '0916666666')->first();

    expect($application)->not->toBeNull()
        ->and($application->bachelor_certificate)->toStartWith('applications/')
        ->and($application->remedial_statement)->toStartWith('applications/');

    Storage::disk('public')->assertExists($application->bachelor_certificate);
    Storage::disk('public')->assertExists($application->remedial_statement);
});
