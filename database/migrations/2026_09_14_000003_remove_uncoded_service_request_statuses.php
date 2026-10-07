<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invalidStatusIds = DB::table('service_request_statuses')
            ->whereRaw("TRIM(COALESCE(code, '')) = ''")
            ->pluck('id');

        if ($invalidStatusIds->isEmpty()) {
            return;
        }

        $fallbackStatusId = DB::table('service_request_statuses')
            ->where('code', 'pending')
            ->value('id')
            ?? DB::table('service_request_statuses')
                ->whereRaw("TRIM(COALESCE(code, '')) <> ''")
                ->orderBy('id')
                ->value('id');

        // Sin un estado operativo al cual moverlas, preservamos las solicitudes y sus estados.
        if (! $fallbackStatusId) {
            return;
        }

        DB::table('service_requests')
            ->whereIn('service_request_status_id', $invalidStatusIds)
            ->update([
                'service_request_status_id' => $fallbackStatusId,
                'updated_at' => now(),
            ]);

        DB::table('service_request_statuses')
            ->whereIn('id', $invalidStatusIds)
            ->delete();
    }

    public function down(): void
    {
        // Los estados heredados retirados no se pueden reconstruir de forma fiable.
    }
};
