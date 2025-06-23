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
        Schema::create('form_incident_question_category_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_incident_question_id');
            $table->unsignedBigInteger('form_incident_category_question_id');
            $table->timestamps();

            $table->foreign('form_incident_question_id', 'fiqcc_question_id_fk')
                ->references('id')->on('form_incident_questions')->onDelete('cascade');
            $table->foreign('form_incident_category_question_id', 'fiqcc_category_id_fk')
                ->references('id')->on('form_incident_category_questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_incident_question_category_question');
    }
};
