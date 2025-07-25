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
        Schema::create('form_incident_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->enum('type', ['si_no', 'abierta', 'seleccion_unica', 'seleccion_multiple']);
            $table->json('options')->nullable();
            $table->boolean('required')->default(true);
            $table->integer('order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_incident_questions');
    }
};
