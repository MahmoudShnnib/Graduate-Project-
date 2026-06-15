<?php

use App\Filament\Pages\CoursesSurveyResults;
use App\Filament\Pages\ExamListPage;
use App\Filament\Pages\ExceptionalOpportunitiesPage;
use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Filament\Resources\Faculty\MyCourseResource;
use App\Filament\Widgets\ActionCenterWidget;
use App\Filament\Widgets\DashboardOverviewWidget;
use App\Filament\Widgets\Faculty\FacultyStatsOverviewWidget;

/**
 * Admin-only surfaces: visible to secretary + super_admin, hidden from faculty.
 * Gated on a Shield permission faculty lack (the page/widget's own View:* perm,
 * or Update:CourseOffering for the resource faculty share the model with).
 *
 * @return array<string, array{0: class-string, 1: string}>
 */
dataset('admin_only_surfaces', [
    'exam list page' => [ExamListPage::class, 'canAccess'],
    'course survey results' => [CoursesSurveyResults::class, 'canAccess'],
    'exceptional opportunities' => [ExceptionalOpportunitiesPage::class, 'canAccess'],
    'dashboard overview widget' => [DashboardOverviewWidget::class, 'canView'],
    'action center widget' => [ActionCenterWidget::class, 'canView'],
    'course offering resource' => [CourseOfferingResource::class, 'canAccess'],
]);

it('hides admin-only surfaces from faculty', function (string $class, string $method) {
    $this->actingAs(facultyUser());

    expect($class::$method())->toBeFalse();
})->with('admin_only_surfaces');

it('shows admin-only surfaces to the secretary', function (string $class, string $method) {
    $this->actingAs(secretaryUser());

    expect($class::$method())->toBeTrue();
})->with('admin_only_surfaces');

it('shows admin-only surfaces to the super admin', function (string $class, string $method) {
    $this->actingAs(adminUser());

    expect($class::$method())->toBeTrue();
})->with('admin_only_surfaces');

it('gates the exam list on the Shield permission, not the role column', function () {
    // A user whose role column says "faculty" but who has been GRANTED the
    // page permission must get in — proving Shield is the source of truth.
    $user = facultyUser();
    $user->givePermissionTo('View:ExamListPage');

    $this->actingAs($user);

    expect(ExamListPage::canAccess())->toBeTrue();
});

it('shows faculty-only surfaces to faculty but not to admins', function () {
    $this->actingAs(facultyUser());
    expect(FacultyStatsOverviewWidget::canView())->toBeTrue()
        ->and(MyCourseResource::canAccess())->toBeTrue();

    // Faculty-only is an identity check (hasRole), so super_admin — who passes
    // every can() via Gate::before — must still NOT see the faculty widget.
    $this->actingAs(adminUser());
    expect(FacultyStatsOverviewWidget::canView())->toBeFalse()
        ->and(MyCourseResource::canAccess())->toBeFalse();

    $this->actingAs(secretaryUser());
    expect(FacultyStatsOverviewWidget::canView())->toBeFalse()
        ->and(MyCourseResource::canAccess())->toBeFalse();
});
