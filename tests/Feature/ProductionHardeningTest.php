<?php

use App\Filament\Pages\ReportsPage;
use App\Models\Specialization;
use App\Models\Student;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

use function Pest\Laravel\actingAs;

/*
 * M1 — print routes must be authorized per role, not merely authenticated.
 * A logged-in student must never reach another record's print output (IDOR guard).
 */
it('forbids students from reaching admin-only print routes', function () {
    ['user' => $student] = studentWithRecord();
    $specialization = Specialization::factory()->create();

    actingAs($student)
        ->get(route('print.exam-list', $specialization))
        ->assertForbidden();
});

it('forbids non-admin faculty from another course offering print route', function () {
    $faculty = \App\Models\User::factory()->withRole('faculty')->create();
    $offering = \App\Models\CourseOffering::factory()->create();

    actingAs($faculty)
        ->get(route('print.course-results', $offering))
        ->assertForbidden();
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
 * M8 — the reports student picker must lazily cap results, not load every student.
 */
it('caps the reports student picker at 50 results', function () {
    actingAs(adminUser());

    Student::factory()->count(60)->create();

    $students = (new ReportsPage)->getStudentsProperty();

    expect($students)->toHaveCount(50);
});

it('narrows the reports student picker by search term', function () {
    actingAs(adminUser());

    Student::factory()->count(20)->create();
    $target = Student::factory()->create();

    $page = new ReportsPage;
    $page->studentSearch = $target->user->name;

    $results = $page->getStudentsProperty();

    expect($results->keys())->toContain($target->id)
        ->and($results->count())->toBeLessThanOrEqual(50);
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
