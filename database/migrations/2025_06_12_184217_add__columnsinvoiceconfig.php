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
        Schema::table('invoice_configs', callback: function (Blueprint $table) {
            $table->unsignedBigInteger('aprobe_user_id')->nullable()->after('status');
            $table->timestamp('aprobe_date')->nullable()->after('aprobe_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_configs', function (Blueprint $table) {
            $table->dropColumn(['aprobe_user_id', 'aprobe_date']);
        });
    }
};
