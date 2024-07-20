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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_responsible_people_id')->nullable()->constrained();
            $table->foreignId('service_request_status_id')->constrained();
            $table->foreignId('service_request_type_id')->constrained();
            $table->foreignId('service_id')->constrained();

            $table->foreignId('lote_id')->nullable()->constrained();
            $table->foreignId('propertie_id')->nullable()->constrained();
            $table->foreignId('owner_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            
            $table->string('name');

            $table->string('model')->nullable();
            $table->string('model_id')->nullable();
            $table->string('options')->nullable();
            $table->string('observations')->nullable();

            $table->string('starts_at');
            $table->string('ends_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
