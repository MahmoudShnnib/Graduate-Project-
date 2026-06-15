<?php

use App\Models\Specialization;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

use function Pest\Laravel\actingAs;

/*
 * M1 — print routes must be authorized per role, not merely authenticated.
 * A logged-in student must never reach another record's print output (IDOR guard).
 */
it('forbids students from reaching admin-only print routes', function () {
    ['user' => $student] = studentWithRecord();
    $specialization = Specialization::factory()->create();

    // Denied: bootstrap/app.php converts the 403 into a logout + redirect to the
    // student's own panel login, so the student never receives the admin PDF.
    actingAs($student)
        ->get(route('print.exam-list', $specialization))
        ->assertRedirect('/portal/login');
});

it('forbids non-admin faculty from another course offering print route', function () {
    $faculty = \App\Models\User::factory()->withRole('faculty')->create();
    $offering = \App\Models\CourseOffering::factory()->create();

    actingAs($faculty)
        ->get(route('print.course-results', $offering))
        ->assertForbidden();
});

it('lets an admin print the blank clearance form but forbids students', function () {
    ['user' => $student] = studentWithRecord();

    // Admin-only blank template printed from the Graduates list — no student context.
    actingAs($student)
        ->get(route('print.clearance-blank'))
        ->assertRedirect('/portal/login');

    actingAs(adminUser())
        ->get(route('print.clearance-blank'))
        ->assertOk();
});

/*
 * M2 — the public /apply submission must be rate limited (throttle:6,1).
 */
it('throttles repeated application submissions', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $lastStatus = null;
    for ($i = 0; $i < 7; $i++) {
        $lastStatus = $this->post('/apply', [])->getStatusCode();
    }

    expect($lastStatus)->toBe(429);
});

/*
 * M9 — entrypoint first-deploy detection runs through a dedicated artisan command
 * (exit 0 = fresh) instead of parsing tinker stdout.
 */
it('reports a fresh install only when no users exist', function () {
    $this->artisan('app:check-fresh-install')->assertExitCode(0);

    adminUser();

    $this->artisan('app:check-fresh-install')->assertExitCode(1);
});
