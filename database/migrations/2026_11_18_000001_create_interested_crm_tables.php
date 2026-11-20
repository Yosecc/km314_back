<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'view_interested::status', 'view_any_interested::status', 'create_interested::status',
        'update_interested::status', 'delete_interested::status', 'delete_any_interested::status',
        'view_interested::contact', 'view_any_interested::contact', 'create_interested::contact',
        'update_interested::contact', 'delete_interested::contact', 'delete_any_interested::contact',
        'view_interested::follow::up', 'view_any_interested::follow::up', 'create_interested::follow::up',
        'update_interested::follow::up', 'delete_interested::follow::up', 'delete_any_interested::follow::up',
        'complete_interested::follow::up', 'reschedule_interested::follow::up', 'assign_interested',
        'page_InterestedMonitor', 'page_InterestedCalendar', 'widget_InterestedFollowUpStats',
    ];

    public function up(): void
    {
        Schema::create('interested_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 30)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('interested_statuses')->insert([
            ['name' => 'Nuevo', 'color' => 'info', 'sort_order' => 10, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Primer contacto pendiente', 'color' => 'warning', 'sort_order' => 20, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Contactado', 'color' => 'primary', 'sort_order' => 30, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'En seguimiento', 'color' => 'warning', 'sort_order' => 40, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Visita programada', 'color' => 'success', 'sort_order' => 50, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Negociación', 'color' => 'success', 'sort_order' => 60, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Convertido', 'color' => 'success', 'sort_order' => 70, 'is_closed' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'No interesado', 'color' => 'danger', 'sort_order' => 80, 'is_closed' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sin respuesta', 'color' => 'gray', 'sort_order' => 90, 'is_closed' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('interesteds', function (Blueprint $table) {
            $table->foreignId('interested_status_id')->nullable()->after('interested_level_id')->constrained('interested_statuses')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->after('interested_status_id')->constrained('users')->nullOnDelete();
            $table->dateTime('last_contacted_at')->nullable()->after('assigned_user_id')->index();
            $table->dateTime('next_contact_at')->nullable()->after('last_contacted_at')->index();
        });

        $newStatusId = DB::table('interested_statuses')->where('name', 'Nuevo')->value('id');
        DB::table('interesteds')->whereNull('interested_status_id')->update(['interested_status_id' => $newStatusId]);

        Schema::create('interested_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interested_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 30);
            $table->string('direction', 20)->default('outgoing');
            $table->string('result', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('contacted_at')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['interested_id', 'contacted_at']);
        });

        Schema::create('interested_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interested_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('channel', 30)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('due_at')->index();
            $table->dateTime('reminder_at')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['interested_id', 'status', 'due_at']);
            $table->index(['assigned_user_id', 'status', 'due_at']);
        });

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interested_follow_ups');
        Schema::dropIfExists('interested_contacts');

        Schema::table('interesteds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropConstrainedForeignId('interested_status_id');
            $table->dropColumn(['last_contacted_at', 'next_contact_at']);
        });

        Schema::dropIfExists('interested_statuses');

        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
