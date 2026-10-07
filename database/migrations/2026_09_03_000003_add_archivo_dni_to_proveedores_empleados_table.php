<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_empleados', function (Blueprint $table) {
            $table->text('archivo_dni')->nullable()->after('dni');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_empleados', function (Blueprint $table) {
            $table->dropColumn('archivo_dni');
        });
    }
};
