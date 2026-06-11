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
