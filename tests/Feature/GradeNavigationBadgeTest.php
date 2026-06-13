<?php

use App\Enums\GradeStatus;
use App\Filament\Resources\Grades\GradeResource;
use App\Models\Grade;

it('shows no navigation badge when nothing awaits approval', function () {
    Grade::factory()->count(2)->approved()->create();

    expect(GradeResource::getNavigationBadge())->toBeNull();
});

it('counts grades pending approval in the navigation badge', function () {
    Grade::factory()->count(3)->create(['status' => GradeStatus::PendingApproval]);
    Grade::factory()->approved()->create();

    expect(GradeResource::getNavigationBadge())->toBe('3')
        ->and(GradeResource::getNavigationBadgeColor())->toBe('warning');
});
