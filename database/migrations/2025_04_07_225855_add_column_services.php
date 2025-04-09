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
        Schema::table('services', function (Blueprint $table) {
            $table->bool('isDateInicio')->default(true);
            $table->bool('isDateFin')->default(false);
            $table->bool('status')->default(false);

        });
        Schema::table('service_types', function (Blueprint $table) {
            $table->bool('status')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
