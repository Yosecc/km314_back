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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained();
            $table->bigInteger('dni');
            $table->string('first_name'); // Campo para el nombre
            $table->string('last_name'); // Campo para el apellido
            $table->bigInteger('phone')->nullable(); // Campo para el número de teléfono
            $table->foreignId('user_id')->constrained('users');

            $table->foreignId('trabajo_id')->constrained('trabajos')->nullable();
            $table->string('model_origen')->nullable();
            $table->bigInteger('model_origen_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
