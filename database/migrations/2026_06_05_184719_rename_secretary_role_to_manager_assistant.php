<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Rename the "secretary" role key to "manager_assistant" in existing data.
     * Touches the simple `users.role` string column and the Spatie `roles` row.
     * Renaming preserves `roles.id`, so existing assignments (model_has_roles)
     * and permissions (role_has_permissions) stay intact.
     *
     * Note: in some DBs the Spatie role was manually relabelled to the Arabic
     * "مساعد اداري"; both that and the seeder default "secretary" map to the new key.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'secretary')->update(['role' => 'manager_assistant']);

        DB::table('roles')->whereIn('name', ['secretary', 'مساعد اداري'])->update(['name' => 'manager_assistant']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'manager_assistant')->update(['role' => 'secretary']);

        DB::table('roles')->where('name', 'manager_assistant')->update(['name' => 'secretary']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
