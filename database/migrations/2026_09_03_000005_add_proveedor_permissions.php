<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_any_proveedor',
        'view_proveedor',
        'create_proveedor',
        'update_proveedor',
        'delete_proveedor',
        'delete_any_proveedor',
        'force_delete_proveedor',
        'force_delete_any_proveedor',
        'restore_proveedor',
        'restore_any_proveedor',
        'replicate_proveedor',
        'reorder_proveedor',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('name', ['super_admin', 'Administrador'])
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach (self::PERMISSIONS as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->value('id');

            if (!$permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
