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
        Schema::create('interesteds', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Campo para el nombre
            $table->string('last_name'); // Campo para el apellido
            $table->string('email')->unique(); // Campo para el correo electrónico
            $table->integer('phone')->nullable(); // Campo para el número de teléfono
            $table->string('address')->nullable(); // Campo para la dirección
            $table->string('city')->nullable(); // Campo para la ciudad
            $table->string('state')->nullable(); // Campo para el estado
            $table->string('zip_code')->nullable(); // Campo para el código postal
            $table->string('country')->nullable(); // Campo para el país
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interesteds');
    }
};
