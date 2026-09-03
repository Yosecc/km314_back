<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa');
            $table->string('telefono_empresa');
            $table->string('cuit_empresa')->nullable();
            $table->string('nombre_responsable')->nullable();
            $table->string('telefono_responsable')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE autos MODIFY model ENUM('Employee', 'Owner', 'FormControl', 'OwnerFamily', 'RecurrentVisitor', 'Proveedor') NOT NULL");
    }

    public function down(): void
    {
        DB::table('autos')->where('model', 'Proveedor')->delete();
        DB::statement("ALTER TABLE autos MODIFY model ENUM('Employee', 'Owner', 'FormControl', 'OwnerFamily', 'RecurrentVisitor') NOT NULL");

        Schema::dropIfExists('proveedores');
    }
};
