<?php

use App\Models\Application;
use App\Models\Semester;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

const CLOSED_NOTICE = 'باب التقديم مغلق حالياً';
const CLOSED_ERROR = 'باب التقديم مغلق حالياً. يرجى المحاولة لاحقاً أو التواصل مع مكتب الدراسات العليا.';

it('reports acceptsApplications correctly on the semester model', function () {
    expect(Semester::factory()->create()->acceptsApplications())->toBeTrue();
    expect(Semester::factory()->applicationsClosed()->create()->acceptsApplications())->toBeFalse();
    expect(Semester::factory()->create(['application_deadline' => now()->subDay()])->acceptsApplications())->toBeFalse();
    expect(Semester::factory()->create(['application_deadline' => now()->addDay()])->acceptsApplications())->toBeTrue();
});

it('shows the apply form when the active semester accepts applications', function () {
    Semester::factory()->active()->create();

    get(route('registration.create'))
        ->assertOk()
        ->assertSee('استمارة طلب التحاق')
        ->assertDontSee(CLOSED_NOTICE);
});

it('shows a closed notice when there is no active semester', function () {
    get(route('registration.create'))
        ->assertOk()
        ->assertSee(CLOSED_NOTICE);
});

it('shows a closed notice when applications are toggled off', function () {
    Semester::factory()->active()->applicationsClosed()->create();

    get(route('registration.create'))
        ->assertOk()
        ->assertSee(CLOSED_NOTICE);
});

it('shows a closed notice when the deadline has passed', function () {
    Semester::factory()->active()->create(['application_deadline' => now()->subDay()]);

    get(route('registration.create'))
        ->assertOk()
        ->assertSee(CLOSED_NOTICE);
});

it('blocks submission and creates nothing when applications are closed', function () {
    Semester::factory()->active()->applicationsClosed()->create();

    post(route('registration.store'), [])
        ->assertSessionHasErrors(['name' => CLOSED_ERROR]);

    expect(Application::count())->toBe(0);
});

it('passes the open-window gate when applications are open', function () {
    Semester::factory()->active()->create();

    // An empty payload still fails normal validation, but NOT with the closed-window
    // error — proving the gate let the request through to validation.
    post(route('registration.store'), [])
        ->assertSessionHasErrors(['name' => 'يرجى إدخال الاسم الرباعي واللقب']);
});
