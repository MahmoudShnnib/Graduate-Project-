<?php

use App\Models\Application;
use App\Models\Specialization;

beforeEach(function () {
    // Disable the WhatsApp OTP gate so submissions are judged purely on the
    // validation rules under test (Qonvo may be configured in the dev env).
    config()->set('services.qonvo.api_token', null);
    config()->set('services.qonvo.sender_phone', null);
});

function nationalityPayload(array $overrides = []): array
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
        'national_id' => '119900115001',
        'residence' => 'طرابلس',
        'permanent_address' => 'طرابلس شارع الجمهورية',
        'study_type' => 'self_funded',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'declaration' => '1',
    ], $overrides);
}

it('accepts a Libyan applicant with a 12-digit national number and stores the Arabic country name', function () {
    $this->post(route('registration.store'), nationalityPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'))
        ->assertSessionHas('tracking_code');

    expect(Application::where('nationality', 'ليبيا')->where('national_id', '119900115001')->exists())->toBeTrue();
});

it('rejects a Libyan applicant whose national number is not 12 digits', function () {
    $this->post(route('registration.store'), nationalityPayload(['national_id' => '12345']))
        ->assertSessionHasErrors('national_id');
});

it('shows the error popup after a failed submission', function () {
    $this->from(route('registration.create'))
        ->followingRedirects()
        ->post(route('registration.store'), nationalityPayload(['national_id' => '12345']))
        ->assertOk()
        ->assertSee('تعذّر إرسال الطلب', false)
        ->assertSee('الرقم الوطني يتكون من 12 رقماً بالضبط', false);
});

it('accepts a non-Libyan applicant with an alphanumeric passport number', function () {
    $this->post(route('registration.store'), nationalityPayload([
        'nationality' => 'مصر',
        'national_id' => 'A1234567',
    ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('registration.create'));

    expect(Application::where('nationality', 'مصر')->where('national_id', 'A1234567')->exists())->toBeTrue();
});

it('rejects a non-Libyan applicant whose ID has invalid characters', function () {
    $this->post(route('registration.store'), nationalityPayload([
        'nationality' => 'مصر',
        'national_id' => 'A!',
    ]))
        ->assertSessionHasErrors('national_id');
});

it('rejects a nationality that is not in the allowed list', function () {
    $this->post(route('registration.store'), nationalityPayload(['nationality' => 'أتلانتس']))
        ->assertSessionHasErrors('nationality');
});

it('rejects a profession that contains english letters (arabic-only)', function () {
    $this->post(route('registration.store'), nationalityPayload(['profession' => 'Engineer']))
        ->assertSessionHasErrors('profession');
});

it('rejects a workplace that contains english letters (arabic-only)', function () {
    $this->post(route('registration.store'), nationalityPayload(['workplace' => 'Tripoli Company']))
        ->assertSessionHasErrors('workplace');
});

it('accepts an arabic profession and workplace', function () {
    $this->post(route('registration.store'), nationalityPayload([
        'profession' => 'مهندس',
        'workplace' => 'شركة الاتصالات',
    ]))->assertSessionHasNoErrors();
});
