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
        Schema::table('form_control_people', function (Blueprint $table) {
            $table->string('file_dni')->nullable()->after('is_menor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_control_people', function (Blueprint $table) {
            $table->dropColumn('file_dni');
        });
    }
};
