<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_request_statuses', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique()->after('name');
        });

        Schema::table('service_requests', function (Blueprint $table): void {
            $table->timestamp('status_changed_at')->nullable()->after('asignado_status_id');
            $table->foreignId('status_changed_by')->nullable()->after('status_changed_at')->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable()->after('status_changed_by');
            $table->index(['owner_id', 'created_at'], 'service_requests_owner_created_index');
            $table->index(['service_request_status_id', 'created_at'], 'service_requests_status_created_index');
        });

        $aliases = [
            'pending' => ['pendiente', 'pendientes', 'nueva', 'nuevo', 'solicitada'],
            'in_progress' => ['en proceso', 'en progreso', 'asignada', 'asignado'],
            'completed' => ['finalizada', 'finalizado', 'completada', 'completado', 'resuelta', 'resuelto'],
            'rejected' => ['rechazada', 'rechazado'],
            'cancelled' => ['cancelada', 'cancelado'],
        ];

        foreach (DB::table('service_request_statuses')->orderBy('id')->get() as $status) {
            $name = mb_strtolower(trim(iconv('UTF-8', 'ASCII//TRANSLIT', $status->name) ?: $status->name));
            foreach ($aliases as $code => $names) {
                if (in_array($name, $names, true)) {
                    DB::table('service_request_statuses')->where('id', $status->id)->update(['code' => $code]);
                    break;
                }
            }
        }

        foreach ([
            ['code' => 'pending', 'name' => 'Pendiente', 'color' => '#d97706'],
            ['code' => 'in_progress', 'name' => 'En proceso', 'color' => '#2563eb'],
            ['code' => 'completed', 'name' => 'Finalizada', 'color' => '#16a34a'],
            ['code' => 'rejected', 'name' => 'Rechazada', 'color' => '#dc2626'],
            ['code' => 'cancelled', 'name' => 'Cancelada', 'color' => '#6b7280'],
        ] as $status) {
            DB::table('service_request_statuses')->updateOrInsert(['code' => $status['code']], $status + [
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            $table->dropIndex('service_requests_owner_created_index');
            $table->dropIndex('service_requests_status_created_index');
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropColumn(['status_changed_at', 'resolution_notes']);
        });

        Schema::table('service_request_statuses', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
