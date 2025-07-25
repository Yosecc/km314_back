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
        Schema::create('form_incident_question_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_incident_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_incident_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_incident_question_type');
    }
};
