<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'view_own_form::control';

    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            ['updated_at' => now(), 'created_at' => now()],
        );

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');
        $superAdminRoleId = DB::table('roles')
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId && $superAdminRoleId) {
            DB::table('role_has_permissions')->where([
                'permission_id' => $permissionId,
                'role_id' => $superAdminRoleId,
            ])->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
