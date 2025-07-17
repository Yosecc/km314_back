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
        Schema::table('invoice_configs', function (Blueprint $table) {
            $table->date('expiration_date');
            $table->date('second_expiration_date');
            $table->integer('punitive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_configs', function (Blueprint $table) {
            $table->dropColumn(['expiration_date', 'second_expiration_date', 'punitive']);
        });
    }
};
