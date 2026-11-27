<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $allCrmPermissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where(function ($query) {
                $query->where('name', 'like', '%interested::status')
                    ->orWhere('name', 'like', '%interested::contact')
                    ->orWhere('name', 'like', '%interested::follow::up')
                    ->orWhereIn('name', ['assign_interested', 'page_InterestedMonitor', 'page_InterestedCalendar', 'widget_InterestedFollowUpStats']);
            })->pluck('id');

        $superAdminId = DB::table('roles')->where('name', 'super_admin')->where('guard_name', 'web')->value('id');
        $this->assign($superAdminId, $allCrmPermissions);

        $viewRoleIds = $this->rolesWithPermission('view_any_interested');
        $this->assignToRoles($viewRoleIds, $this->permissionIds([
            'page_InterestedMonitor', 'page_InterestedCalendar', 'widget_InterestedFollowUpStats',
            'view_interested::contact', 'view_any_interested::contact',
            'view_interested::follow::up', 'view_any_interested::follow::up',
        ]));

        $createRoleIds = $this->rolesWithPermission('create_interested');
        $this->assignToRoles($createRoleIds, $this->permissionIds([
            'create_interested::contact', 'create_interested::follow::up',
            'complete_interested::follow::up', 'reschedule_interested::follow::up',
        ]));

        $updateRoleIds = $this->rolesWithPermission('update_interested');
        $this->assignToRoles($updateRoleIds, $this->permissionIds([
            'update_interested::contact', 'update_interested::follow::up', 'assign_interested',
        ]));
    }

    public function down(): void
    {
        // Las asignaciones se conservan para no quitar permisos elegidos luego desde Shield.
    }

    private function rolesWithPermission(string $permission): array
    {
        $permissionId = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->value('id');
        if (! $permissionId) {
            return [];
        }

        return DB::table('role_has_permissions')->where('permission_id', $permissionId)->pluck('role_id')->all();
    }

    private function permissionIds(array $names)
    {
        return DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $names)->pluck('id');
    }

    private function assignToRoles(array $roleIds, $permissionIds): void
    {
        foreach ($roleIds as $roleId) {
            $this->assign($roleId, $permissionIds);
        }
    }

    private function assign(?int $roleId, $permissionIds): void
    {
        if (! $roleId) {
            return;
        }
        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId, 'role_id' => $roleId,
            ]);
        }
    }
};
