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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('expense_concept_id')->constrained();
            $table->foreignId('lote_id')->nullable()->constrained();
            $table->foreignId('propertie_id')->nullable()->constrained();
            $table->foreignId('owner_id')->nullable()->constrained();
            $table->foreignId('expense_status_id')->constrained();

            $table->date('date_prox_payment');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
