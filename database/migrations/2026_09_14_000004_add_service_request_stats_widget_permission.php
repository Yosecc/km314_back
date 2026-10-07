<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'widget_ServiceRequestStats', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );

        $widgetPermissionId = DB::table('permissions')
            ->where('name', 'widget_ServiceRequestStats')
            ->value('id');
        $monitorPermissionId = DB::table('permissions')
            ->where('name', 'page_ServiceRequestMonitor')
            ->value('id');

        if (! $widgetPermissionId || ! $monitorPermissionId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $monitorPermissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $widgetPermissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'widget_ServiceRequestStats')->value('id');

        if (! $id) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }
};
