<?php

use App\Filament\Widgets\DashboardHeaderWidget;
use App\Filament\Widgets\DashboardOverviewWidget;
use App\Models\Semester;
use App\Models\User;
use Livewire\Livewire;

it('renders the admin dashboard overview widget with funnel and attention sections', function () {
    $this->actingAs(adminUser());
    Semester::factory()->active()->create(['name' => 'الفصل الحالي']);

    Livewire::test(DashboardOverviewWidget::class)
        ->assertOk()
        ->assertSee('مسار القبول')
        ->assertSee('يحتاج إجراء');
});

it('loads the full admin dashboard page without error', function () {
    $this->actingAs(adminUser());
    Semester::factory()->active()->create(['name' => 'الفصل الحالي']);

    // assertOk proves the full dashboard page builds with all registered
    // widgets; the widget's own rendered content is covered by the Livewire
    // test above (Filament defers widget HTML to a follow-up request).
    $this->get('/admin')->assertOk();
});

it('renders the welcome+settings header banner with the settings button for admins', function () {
    $admin = adminUser();
    $this->actingAs($admin);
    Semester::factory()->active()->create(['name' => 'الفصل الحالي']);

    Livewire::test(DashboardHeaderWidget::class)
        ->assertOk()
        ->assertSee('مرحباً')
        ->assertSee($admin->name)
        ->assertSee('إعدادات النظام')
        ->assertSee('تسجيل الخروج');
});

it('hides the settings button from non-admin roles in the header banner', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $this->actingAs($faculty);
    Semester::factory()->active()->create();

    Livewire::test(DashboardHeaderWidget::class)
        ->assertOk()
        ->assertSee('مرحباً')
        ->assertDontSee('إعدادات النظام');
});

it('hides the admin overview widget from faculty', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $this->actingAs($faculty);

    expect(DashboardOverviewWidget::canView())->toBeFalse();
});

it('computes a monotonic admissions funnel for the active semester', function () {
    $this->actingAs(adminUser());
    Semester::factory()->active()->create();

    $data = (new DashboardOverviewWidget)->getDashboardData();

    expect($data['funnel']['stages'])->toHaveCount(3)
        ->and($data['kpis'])->toHaveCount(4)
        ->and($data['attention'])->toHaveCount(6);

    $counts = array_column($data['funnel']['stages'], 'count');
    expect($counts[0])->toBeGreaterThanOrEqual($counts[1])
        ->and($counts[1])->toBeGreaterThanOrEqual($counts[2]);
});
