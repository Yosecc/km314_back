<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $bookingPermissions = [
        'view_booking',
        'view_any_booking',
        'create_booking',
        'update_booking',
        'restore_booking',
        'restore_any_booking',
        'replicate_booking',
        'reorder_booking',
        'delete_booking',
        'delete_any_booking',
        'force_delete_booking',
        'force_delete_any_booking',
    ];

    public function up(): void
    {
        Schema::table('interesteds', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });

        $now = now();

        foreach ($this->bookingPermissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('amount')->nullable()->change();
        });

        Schema::table('interesteds', function (Blueprint $table) {
            $table->bigInteger('phone')->nullable()->change();
        });

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->bookingPermissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
