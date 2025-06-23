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
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('question_id', 'fiqcc_qid_fk')
                ->references('id')->on('form_incident_questions')->onDelete('cascade');
            $table->foreign('category_id', 'fiqcc_cid_fk')
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
