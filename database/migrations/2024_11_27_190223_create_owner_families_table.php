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
        Schema::create('owner_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained();
            $table->bigInteger('dni')->nullable();
            $table->string('first_name'); // Campo para el nombre
            $table->string('last_name'); // Campo para el apellido
            $table->string('parentage')->nullabble();
            $table->boolean('is_menor')->default(false);
            $table->bigInteger('phone')->nullable(); //
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_families');
    }
};
