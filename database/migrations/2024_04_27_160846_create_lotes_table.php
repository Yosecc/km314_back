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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('width');
            $table->string('height');
            $table->string('m2');
            $table->foreignId('sector_id')->constrained();
            $table->integer('lote_id');
            $table->text('ubication')->nullable();
            $table->foreignId('lote_type_id')->constrained();
            $table->foreignId('lote_status_id')->constrained();
            $table->foreignId('owner_id')->nullable()->constrained();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
