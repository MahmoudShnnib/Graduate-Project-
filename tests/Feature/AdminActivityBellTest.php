<?php

use App\Livewire\AdminActivityBell;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Livewire\Livewire;

/**
 * A super-admin who manages the panel and monitors the bell.
 */
function bellViewer(): User
{
    return adminUser();
}

/**
 * Another staff member whose activity the viewer should see.
 */
function bellActor(string $role = 'manager_assistant'): User
{
    return User::factory()->withRole($role)->create();
}

function bellLog(User $author, array $attrs = []): AuditLog
{
    return AuditLog::create(array_merge([
        'user_id' => $author->id,
        'action' => 'application_approved',
        'model_type' => Application::class,
        'model_id' => 1,
        'subject_label' => null,
        'created_at' => now(),
    ], $attrs));
}

it('shows activity by other admins/managers but never the viewer own actions', function () {
    $viewer = bellViewer();
    $actor = bellActor();

    bellLog($viewer); // mine — must be hidden
    bellLog($actor);  // someone else — must show

    Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->assertSet('badgeCount', 1);
});

it('ignores activity by faculty and students (only admins + managers)', function () {
    $viewer = bellViewer();

    bellLog(bellActor('manager_assistant'));
    bellLog(bellActor('faculty'));
    bellLog(bellActor('student'));

    Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->assertSet('badgeCount', 1);
});

it('collapses generic edits into the decision they belong to (same batch)', function () {
    $viewer = bellViewer();
    $actor = bellActor();

    // One operation: a decision + the raw status edit it produced, same batch.
    bellLog($actor, ['batch_id' => 'op-1', 'action' => 'application_approved', 'subject_label' => 'محمد سالم']);
    bellLog($actor, ['batch_id' => 'op-1', 'action' => 'Application_updated']);

    $component = Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->assertSet('badgeCount', 1); // only the decision line survives

    expect($component->get('activities')[0]['title'])->toContain('قبول')
        ->and($component->get('activities')[0]['title'])->toContain('محمد سالم');
});

it('shows a standalone edit when its batch has no decision', function () {
    $viewer = bellViewer();
    $actor = bellActor();

    bellLog($actor, ['batch_id' => 'edit-1', 'action' => 'Student_updated', 'model_type' => Student::class, 'subject_label' => 'أحمد المنصوري']);

    $component = Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->assertSet('badgeCount', 1);

    expect($component->get('activities')[0]['title'])->toContain('تعديل')
        ->and($component->get('activities')[0]['title'])->toContain('أحمد المنصوري');
});

it('summarizes a create with an add label and the subject name', function () {
    $viewer = bellViewer();
    $actor = bellActor();

    bellLog($actor, ['action' => 'Student_created', 'model_type' => Student::class, 'subject_label' => 'سعاد علي']);

    $component = Livewire::actingAs($viewer)->test(AdminActivityBell::class);

    expect($component->get('activities')[0]['title'])->toBe('إضافة طالب: سعاد علي');
});

it('clears the badge and advances last_seen when marked seen, keeping items visible', function () {
    $viewer = bellViewer();
    $actor = bellActor();
    bellLog($actor);
    $latest = bellLog($actor);

    $component = Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->assertSet('badgeCount', 2)
        ->call('markSeen')
        ->assertSet('badgeCount', 0)
        ->assertCount('activities', 2);

    expect($viewer->fresh()->last_seen_activity_id)->toBe($latest->id);
});

it('drops seen items on the next refresh and resurfaces brand-new ones', function () {
    $viewer = bellViewer();
    $actor = bellActor();
    bellLog($actor);

    $component = Livewire::actingAs($viewer)
        ->test(AdminActivityBell::class)
        ->call('markSeen')
        ->call('refresh')
        ->assertSet('badgeCount', 0);

    bellLog($actor); // something new happens

    $component->call('refresh')->assertSet('badgeCount', 1);
});

it('records create / update / delete to the audit log for every tracked model', function () {
    ['student' => $student] = studentWithRecord();

    expect(AuditLog::where('action', 'Student_created')->where('model_id', $student->id)->exists())->toBeTrue();

    $student->update(['enrollment_no' => 'TEST-123']);
    expect(AuditLog::where('action', 'Student_updated')->where('model_id', $student->id)->exists())->toBeTrue();

    $student->delete();
    expect(AuditLog::where('action', 'Student_deleted')->where('model_id', $student->id)->exists())->toBeTrue();
});
