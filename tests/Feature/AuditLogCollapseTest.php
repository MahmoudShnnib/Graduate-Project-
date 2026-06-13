<?php

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function logRow(User $user, string $action, ?string $batch, array $extra = []): AuditLog
{
    return AuditLog::create(array_merge([
        'user_id' => $user->id,
        'batch_id' => $batch,
        'action' => $action,
        'model_type' => 'App\\Models\\Application',
        'model_id' => 1,
        'created_at' => now(),
    ], $extra));
}

it('collapses one operation into its decision row and counts the batch size', function () {
    $user = User::factory()->withRole('super_admin')->create();
    logRow($user, 'Application_updated', 'op-1');
    logRow($user, 'User_created', 'op-1');
    $decision = logRow($user, 'application_approved', 'op-1');

    $rows = AuditLogResource::collapsedQuery()->where('batch_id', 'op-1')->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->id)->toBe($decision->id)
        ->and((int) $rows->first()->batch_size)->toBe(3);
});

it('picks the earliest row when a batch has only generic edits', function () {
    $user = User::factory()->withRole('super_admin')->create();
    $first = logRow($user, 'Student_updated', 'op-2');
    logRow($user, 'StudentEnrollment_updated', 'op-2');

    $rows = AuditLogResource::collapsedQuery()->where('batch_id', 'op-2')->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->id)->toBe($first->id);
});

it('keeps rows without a batch as separate standalone entries', function () {
    $user = User::factory()->withRole('super_admin')->create();
    logRow($user, 'Student_updated', null);
    logRow($user, 'Course_updated', null);

    expect(AuditLogResource::collapsedQuery()->whereNull('batch_id')->count())->toBe(2);
});

it('hides system rows that have no user', function () {
    AuditLog::create([
        'user_id' => null, 'batch_id' => 'op-3', 'action' => 'Setting_updated',
        'model_type' => 'App\\Models\\Setting', 'model_id' => 1, 'created_at' => now(),
    ]);

    expect(AuditLogResource::collapsedQuery()->where('batch_id', 'op-3')->count())->toBe(0);
});

it('collapses operations in the real list page (getAuditLogs) and exposes the batch size', function () {
    $admin = adminUser();
    $generic = logRow($admin, 'Student_updated', 'op-5', ['model_type' => 'App\\Models\\Student']);
    $decision = logRow($admin, 'application_approved', 'op-5');

    $this->actingAs($admin);

    $data = Livewire::test(ListAuditLogs::class)->instance()->getAuditLogs();
    $items = $data['items'];

    expect($items->pluck('id')->all())->toContain($decision->id)
        ->and($items->pluck('id')->all())->not->toContain($generic->id);

    $rep = $items->firstWhere('id', $decision->id);
    expect((int) $rep->batch_size)->toBe(2);
});

it('reports the decision count so a bulk approval reads honestly, not as "+N edits"', function () {
    $admin = adminUser();
    // One bulk operation: 2 real approvals + their 2 automatic edits, same batch.
    logRow($admin, 'application_initial_approval', 'bulk-1', ['subject_label' => 'طالب أ']);
    logRow($admin, 'application_initial_approval', 'bulk-1', ['subject_label' => 'طالب ب']);
    logRow($admin, 'Application_updated', 'bulk-1');
    logRow($admin, 'Application_updated', 'bulk-1');

    $this->actingAs($admin);

    $rep = Livewire::test(ListAuditLogs::class)->instance()->getAuditLogs()['items']->first();

    expect((int) $rep->batch_size)->toBe(4)
        ->and((int) $rep->batch_decision_count)->toBe(2);
});

it('shows every raw row uncollapsed when "show system" is on', function () {
    $admin = adminUser();
    $generic = logRow($admin, 'Student_updated', 'op-9', ['model_type' => 'App\\Models\\Student']);
    $decision = logRow($admin, 'application_approved', 'op-9');

    $this->actingAs($admin);

    $page = Livewire::test(ListAuditLogs::class)->set('filterShowSystem', true)->instance();
    $ids = $page->getAuditLogs()['items']->pluck('id')->all();

    expect($ids)->toContain($decision->id)->toContain($generic->id);
});

it('batch-aware action filter surfaces the representative of any operation containing that action', function () {
    $admin = adminUser();
    // op-5 contains a Student_updated; op-7 does not.
    logRow($admin, 'Student_updated', 'op-5', ['model_type' => 'App\\Models\\Student']);
    $repWithStudentEdit = logRow($admin, 'application_approved', 'op-5');
    logRow($admin, 'Course_updated', 'op-7', ['model_type' => 'App\\Models\\Course']);
    $repWithout = logRow($admin, 'course_offering_opened', 'op-7');

    $ids = AuditLogResource::applyActionFilter(
        AuditLogResource::collapsedQuery(),
        ['Student_updated'],
    )->pluck('id')->all();

    expect($ids)->toContain($repWithStudentEdit->id)
        ->and($ids)->not->toContain($repWithout->id);
});

it('lists the sibling rows of the operation on the detail page', function () {
    $admin = adminUser();
    $sibling = logRow($admin, 'Application_updated', 'op-6');
    $decision = logRow($admin, 'application_approved', 'op-6');

    $this->actingAs($admin);

    $ids = Livewire::test(ViewAuditLog::class, ['record' => $decision->id])
        ->instance()
        ->getBatchSiblings()
        ->pluck('id')
        ->all();

    expect($ids)->toContain($sibling->id)
        ->and($ids)->not->toContain($decision->id);
});
