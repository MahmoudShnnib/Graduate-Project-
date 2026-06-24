<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Specialization;

use function Pest\Laravel\post;

beforeEach(function (): void {
    Specialization::factory()->create();
});

function trackApplication(Application $application)
{
    return post(route('application.track'), [
        'tracking_code' => $application->tracking_code,
        'national_id' => $application->national_id,
    ]);
}

it('shows the 3-step tracker with review in progress for a pending application', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Pending]);

    trackApplication($application)
        ->assertOk()
        ->assertSee('حالة الطلب')
        ->assertSee('تقديم الطلب')
        ->assertSee('مراجعة الطلب')
        ->assertSee('القرار النهائي')
        ->assertSee('قيد المراجعة')
        ->assertSee('بانتظار القرار')
        // search form is hidden once a result is shown
        ->assertDontSee('استعلام')
        ->assertDontSee('name="tracking_code"', false);
});

it('shows the search form when no application has been looked up', function (): void {
    $this->get(route('application.track.show'))
        ->assertOk()
        ->assertSee('استعلام')
        ->assertSee('name="tracking_code"', false);
});

it('marks review done and decision accepted for an accepted application', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Accepted]);

    trackApplication($application)
        ->assertOk()
        ->assertSee('تمت المراجعة')
        ->assertSee('مقبول');
});

it('marks the final decision rejected for a rejected application', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Rejected]);

    trackApplication($application)
        ->assertOk()
        ->assertSee('تمت المراجعة')
        ->assertSee('مرفوض');
});

it('does not render the hero photo on the track page', function (): void {
    $this->get(route('application.track.show'))
        ->assertOk()
        ->assertDontSee('hero-apply.jpg');
});

it('offers a forgot-code recovery option on the search form', function (): void {
    $this->get(route('application.track.show'))
        ->assertOk()
        ->assertSee('نسيت رمز التتبع؟')
        ->assertSee('name="phone"', false);
});

it('recovers an application by national id and phone when the code is lost', function (): void {
    $application = Application::factory()->create([
        'status' => ApplicationStatus::Pending,
        'national_id' => '120120120120',
        'phone' => '0911111111',
    ]);

    post(route('application.track'), [
        'mode' => 'recovery',
        'national_id' => '120120120120',
        'phone' => '0911111111',
    ])
        ->assertOk()
        ->assertSee('حالة الطلب')
        ->assertSee('مراجعة الطلب');
});

it('rejects recovery when the phone does not match the national id', function (): void {
    Application::factory()->create([
        'national_id' => '120120120120',
        'phone' => '0911111111',
    ]);

    post(route('application.track'), [
        'mode' => 'recovery',
        'national_id' => '120120120120',
        'phone' => '0922222222',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('national_id');
});

it('does not leak an application when only the national id is known without the phone', function (): void {
    Application::factory()->create([
        'national_id' => '120120120120',
        'phone' => '0911111111',
    ]);

    post(route('application.track'), [
        'mode' => 'recovery',
        'national_id' => '120120120120',
        'phone' => '',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('phone');
});
