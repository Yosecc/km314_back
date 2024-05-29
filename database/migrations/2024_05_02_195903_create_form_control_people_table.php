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
        Schema::create('form_control_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_control_id')->constrained()->nullable();
            $table->bigInteger('dni');
            $table->string('first_name'); // Campo para el nombre
            $table->string('last_name'); // Campo para el apellido
            $table->bigInteger('phone')->nullable(); // Campo para el número de teléfono
            $table->boolean('is_responsable')->default(false);
            $table->boolean('is_cliente')->default(false);
            $table->boolean('is_menor')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_control_people');
    }
};
