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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_status_id')->nullable()->constrained();
            $table->foreignId('lote_id')->nullable()->constrained();
            $table->foreignId('interested_id')->nullable()->constrained();
            $table->foreignId('propertie_id')->nullable()->constrained();
            $table->foreignId('interested_type_operation_id')->nullable()->constrained();
            $table->string('operation_detail')->nullable();
            $table->string('amount')->nullable();
            $table->date('date_end');
            $table->softDeletes($column = 'deleted_at', $precision = 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
