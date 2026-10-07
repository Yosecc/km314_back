<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'package_reception_tutorial_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('package_reception_tutorial_seen_at')->nullable()->after('remember_token');
            });
        }
    }

    public function down(): void
    {
        // Esta migración solo repara instalaciones con el esquema desincronizado.
        // No se elimina la columna porque puede pertenecer a la migración original.
    }
};
