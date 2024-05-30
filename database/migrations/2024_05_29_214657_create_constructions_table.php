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
        Schema::create('constructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('construction_type_id')->constrained();
            $table->foreignId('construction_companie_id')->constrained();
            $table->foreignId('construction_status_id')->constrained();
            $table->foreignId('lote_id')->constrained();
            $table->foreignId('owner_id')->constrained();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('m2')->nullable();
            $table->string('observations')->nullable();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constructions');
    }
};
