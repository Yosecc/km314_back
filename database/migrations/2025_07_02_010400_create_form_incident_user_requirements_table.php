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
        Schema::create('form_incident_user_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('form_incident_type_id')->constrained()->onDelete('cascade');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->time('deadline_time'); // Hora límite (ej: 09:00, 23:00)
            $table->json('days_of_week')->nullable(); // Para frecuencia semanal [1,2,3,4,5] (lunes a viernes)
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['user_id', 'is_active']);
            $table->index(['form_incident_type_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_incident_user_requirements');
    }
};
