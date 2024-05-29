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
        Schema::create('activities_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activities_id')->constrained()->onDelete('cascade');
            // $table->foreignId('form_control_people_id')->constrained('form_control_people');
            $table->bigInteger('model_id');
            $table->string('model');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities_people');
    }
};
