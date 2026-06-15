<?php

use App\Filament\Resources\CourseOfferings\CourseOfferingResource;
use App\Filament\Resources\Faculty\MyCourseResource;
use App\Filament\Resources\Grades\GradeResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\User;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

it('hides the admin course-offerings resource from faculty even with the permission', function () {
    $faculty = User::factory()->faculty()->create();
    Permission::firstOrCreate(['name' => 'ViewAny:CourseOffering', 'guard_name' => 'web']);
    $faculty->givePermissionTo('ViewAny:CourseOffering');

    actingAs($faculty);

    expect(CourseOfferingResource::canAccess())->toBeFalse()
        ->and(CourseOfferingResource::shouldRegisterNavigation())->toBeFalse();
});

it('still lets faculty reach their own courses resource', function () {
    $faculty = User::factory()->faculty()->create();
    Permission::firstOrCreate(['name' => 'ViewAny:CourseOffering', 'guard_name' => 'web']);
    $faculty->givePermissionTo('ViewAny:CourseOffering');

    actingAs($faculty);

    expect(MyCourseResource::canAccess())->toBeTrue();
});

it('hides the grades and students resources from faculty (no permission granted)', function () {
    $faculty = User::factory()->faculty()->create();

    actingAs($faculty);

    expect(GradeResource::canAccess())->toBeFalse()
        ->and(StudentResource::canAccess())->toBeFalse();
});

it('keeps the course-offerings resource accessible to a super admin', function () {
    actingAs(adminUser());

    expect(CourseOfferingResource::canAccess())->toBeTrue();
});

it('hides admin-only custom pages from faculty', function () {
    $faculty = User::factory()->faculty()->create();

    actingAs($faculty);

    expect(\App\Filament\Pages\ExamListPage::canAccess())->toBeFalse()
        ->and(\App\Filament\Pages\ExceptionalOpportunitiesPage::canAccess())->toBeFalse()
        ->and(\App\Filament\Pages\CoursesSurveyResults::canAccess())->toBeFalse();
});

it('keeps admin-only custom pages accessible to a super admin', function () {
    actingAs(adminUser());

    expect(\App\Filament\Pages\ExamListPage::canAccess())->toBeTrue();
});
