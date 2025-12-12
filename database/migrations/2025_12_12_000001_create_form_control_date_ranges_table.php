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
        Schema::create('form_control_date_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_control_id')->constrained()->onDelete('cascade');
            $table->date('start_date_range');
            $table->time('start_time_range');
            $table->date('end_date_range')->nullable();
            $table->time('end_time_range')->nullable();
            $table->boolean('date_unilimited')->default(false);
            $table->timestamps();
            
            $table->index('form_control_id');
        });

        // Migrar datos existentes desde form_controls a form_control_date_ranges
        \DB::statement("
            INSERT INTO form_control_date_ranges 
                (form_control_id, start_date_range, start_time_range, end_date_range, end_time_range, date_unilimited, created_at, updated_at)
            SELECT 
                id,
                start_date_range,
                start_time_range,
                end_date_range,
                end_time_range,
                date_unilimited,
                created_at,
                updated_at
            FROM form_controls
            WHERE start_date_range IS NOT NULL 
                AND start_time_range IS NOT NULL
                AND deleted_at IS NULL
        ");

        // Hacer nullable los campos legacy en form_controls
        Schema::table('form_controls', function (Blueprint $table) {
            $table->date('start_date_range')->nullable()->change();
            $table->time('start_time_range')->nullable()->change();
            $table->date('end_date_range')->nullable()->change();
            $table->time('end_time_range')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_control_date_ranges');
    }
};
