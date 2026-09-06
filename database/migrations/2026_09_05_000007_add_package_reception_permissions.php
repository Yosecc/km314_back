<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'view_any_package::reception', 'view_package::reception', 'create_package::reception',
        'update_package::reception', 'delete_package::reception', 'receive_package::reception',
        'cancel_package::reception', 'deliver_package::reception', 'correct_package::reception',
        'view_sensitive_package::reception', 'view_package::reception_monitor',
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $all = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        $owner = DB::table('permissions')->whereIn('name', [
            'view_any_package::reception', 'view_package::reception', 'create_package::reception',
            'update_package::reception', 'cancel_package::reception', 'view_sensitive_package::reception',
        ])->pluck('id');
        $operations = DB::table('permissions')->whereIn('name', [
            'view_any_package::reception', 'view_package::reception', 'receive_package::reception',
            'cancel_package::reception', 'deliver_package::reception', 'view_sensitive_package::reception',
            'view_package::reception_monitor',
        ])->pluck('id');

        foreach (DB::table('roles')->get() as $role) {
            $permissionIds = match (true) {
                in_array($role->name, ['super_admin', 'Administrador'], true) => $all,
                $role->name === 'owner' => $owner,
                str_contains(mb_strtolower($role->name), 'seguridad'), $role->name === 'security' => $operations,
                default => collect(),
            };
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
