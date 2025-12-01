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
        Schema::create('form_control_mascota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_control_id');
            $table->foreign('form_control_id')->references('id')->on('form_controls')->onDelete('cascade');
            $table->string('tipo_mascota');
            $table->string('raza')->nullable();
            $table->string('nombre')->nullable();
            $table->boolean('is_vacunado')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_control_mascota');
    }
};
