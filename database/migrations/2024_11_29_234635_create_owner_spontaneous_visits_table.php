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
        Schema::create('owner_spontaneous_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained();
            $table->bigInteger('dni')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->bigInteger('phone')->nullable();
            $table->boolean('aprobado')->nullable();
            $table->boolean('agregado')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_spontaneous_visits');
    }
};
