<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'view_interested::level',
        'view_any_interested::level',
        'create_interested::level',
        'update_interested::level',
        'delete_interested::level',
        'delete_any_interested::level',
    ];

    public function up(): void
    {
        Schema::create('interested_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('interested_levels')->insert([
            ['name' => 'Bajo', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Medio', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Alto', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('interesteds', function (Blueprint $table) {
            $table->foreignId('interested_level_id')
                ->nullable()
                ->after('interested_type_id')
                ->constrained('interested_levels')
                ->nullOnDelete();
        });

        $mediumLevelId = DB::table('interested_levels')->where('name', 'Medio')->value('id');
        DB::table('interesteds')->whereNull('interested_level_id')->update(['interested_level_id' => $mediumLevelId]);

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::table('interesteds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interested_level_id');
        });

        Schema::dropIfExists('interested_levels');

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
