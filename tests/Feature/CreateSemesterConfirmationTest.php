<?php

use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Models\Semester;
use Filament\Actions\Action;
use Livewire\Livewire;

it('the Create form action is a confirmation-required action (not a plain submit)', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $component = Livewire::test(CreateSemester::class);

    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('getCreateFormAction');
    $method->setAccessible(true);
    $action = $method->invoke($component->instance());

    // Indirect signal that confirmation is configured: a modal heading
    // exists, and the submit-form-livewire binding has been replaced with
    // an action callback (otherwise the modal would be skipped).
    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->getName())->toBe('create')
        ->and($action->getModalHeading())->toBe('إنشاء فصل جديد')
        ->and($action->getModalSubmitActionLabel())->toBe('إنشاء وتنشيط');
});

it('the confirmation modal description names the current active semester when one exists', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $currentActive = Semester::factory()->create([
        'name' => 'خريف 2026',
        'is_active' => true,
    ]);

    $component = Livewire::test(CreateSemester::class);
    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('getCreateFormAction');
    $method->setAccessible(true);
    $action = $method->invoke($component->instance());

    $description = $action->getModalDescription();

    expect((string) $description)->toContain('خريف 2026')
        ->and((string) $description)->toContain('سيتم إغلاق');
});

it('the confirmation modal description handles the no-active-semester case', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    // Ensure no active non-archived semester
    Semester::query()->whereNull('archived_at')->update(['archived_at' => now()]);

    $component = Livewire::test(CreateSemester::class);
    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('getCreateFormAction');
    $method->setAccessible(true);
    $action = $method->invoke($component->instance());

    $description = $action->getModalDescription();

    expect((string) $description)->toContain('لا يوجد فصل حالي');
});

it('after calling the create action, the new semester is is_active=true and a prior active was auto-archived', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $previousActive = Semester::factory()->create([
        'name' => 'خريف سابق',
        'is_active' => true,
    ]);

    Livewire::test(CreateSemester::class)
        ->fillForm([
            'name' => 'ربيع 2027',
            'year' => 2027,
            'season' => 'spring',
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-30',
        ])
        ->call('create');

    $new = Semester::where('name', 'ربيع 2027')->first();
    expect($new)->not()->toBeNull()
        ->and($new->is_active)->toBeTrue()
        ->and($new->archived_at)->toBeNull()
        ->and($previousActive->fresh()->isArchived())->toBeTrue();
});
