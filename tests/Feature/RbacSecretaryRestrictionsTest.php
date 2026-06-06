<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Regression guard for the 2026-05-15 RBAC posture decision (C-3 audit finding).
 *
 * Secretary must NOT be able to read /admin/users, /admin/audit-logs, or
 * /admin/shield/roles. Super-admin still can.
 */
beforeEach(function () {
    $this->secretary = User::factory()->withRole('manager_assistant')->create();
    $secretaryRole = Role::firstOrCreate(['name' => 'manager_assistant', 'guard_name' => 'web']);
    $this->secretary->assignRole($secretaryRole);
});

$lockedRoutes = [
    'users' => '/admin/users',
    'audit logs' => '/admin/audit-logs',
    'shield roles' => '/admin/shield/roles',
];

foreach ($lockedRoutes as $label => $url) {
    it("blocks secretary from {$label}", function () use ($url) {
        $response = $this->actingAs($this->secretary)->get($url);

        // Filament returns 403 on canAccess() failure. If it ever redirects
        // instead, accept that too — the security guarantee is "not 200".
        expect($response->status())->toBeIn([302, 403]);
    });
}

it('still allows super_admin to access all three locked resources', function () use ($lockedRoutes) {
    $admin = adminUser();

    foreach ($lockedRoutes as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});
