<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_configs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('period'); // mes/año de la configuración
            $table->date('fecha_creacion'); // fecha en la que debe ejecutarse la configuración
            $table->json('config'); // objeto de configuración
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_configs');
    }
};
