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
        Schema::create('form_control_people_files', function (Blueprint $table) {
            $table->id();
             $table->text('name');
            $table->text('file');
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->unsignedBigInteger('form_control_people_id');
            $table->foreign('form_control_people_id')->references('id')->on('form_control_people')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_control_people_files');
    }
};
