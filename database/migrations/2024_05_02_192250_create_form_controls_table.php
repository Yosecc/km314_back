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
        Schema::create('form_controls', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_moroso')->default(false);
            $table->string('lote_ids')->nullable();
            $table->string('access_type')->nullable();
            $table->string('income_type')->nullable();
            $table->date('start_date_range');
            $table->time('start_time_range', $precision = 0)->nullable();
            $table->date('end_date_range')->nullable();
            $table->boolean('date_unilimited')->default(false);
            $table->time('end_time_range', $precision = 0)->nullable();
            $table->enum('status', ['Authorized', 'Denied','Pending']);
            // $table->enum('category', ['Owner', 'Tenant', 'Restaurant', 'Frequent works', 'Occasional works']);
            $table->foreignId('authorized_user_id')->nullable()->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->string('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_controls');
    }
};
