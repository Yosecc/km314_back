<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'page_ServiceRequestMonitor', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'page_ServiceRequestMonitor')->value('id');
        if (! $id) return;

        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }
};
