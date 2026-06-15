<?php

use App\Filament\Widgets\ActionCenterWidget;
use App\Models\Application;
use Livewire\Livewire;

/*
 * Admin dashboard "بانتظار إجرائك" Action Center.
 * See _brain/project-context/admin-action-center-guide.md.
 */

it('is visible to super_admin and secretary but hidden from faculty', function () {
    $this->actingAs(adminUser());
    expect(ActionCenterWidget::canView())->toBeTrue();

    $this->actingAs(secretaryUser());
    expect(ActionCenterWidget::canView())->toBeTrue();

    $this->actingAs(facultyUser());
    expect(ActionCenterWidget::canView())->toBeFalse();
});

it('renders the action centre heading and every category card', function () {
    $this->actingAs(adminUser());

    Livewire::test(ActionCenterWidget::class)
        ->assertSee('بانتظار إجرائك')
        ->assertSee('طلبات القبول')
        ->assertSee('طلبات إيقاف القيد')
        ->assertSee('طلبات الإضافة والحذف')
        ->assertSee('تسجيلات الإشراف')
        ->assertSee('طلبات المناقشة')
        ->assertSee('استبيان المقررات')
        ->assertSee('الفرص الاستثنائية')
        ->assertSee('درجات بانتظار الاعتماد');
});

it('counts pending applications', function () {
    foreach (range(1, 3) as $i) {
        Application::factory()->create([
            'tracking_code' => "TRK-{$i}",
            'email' => "app{$i}@example.com",
            'national_id' => "10000000000{$i}",
        ]);
    }

    $this->actingAs(adminUser());

    Livewire::test(ActionCenterWidget::class)
        ->assertSee('طلبات القبول')
        ->assertSee('3');
});

it('shows zero for categories with nothing pending', function () {
    $this->actingAs(adminUser());

    // No records seeded → every card renders its "لا يوجد" hint.
    Livewire::test(ActionCenterWidget::class)
        ->assertSee('لا يوجد');
});
