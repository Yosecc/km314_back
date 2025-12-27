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
        Schema::table('autos', function (Blueprint $table) {
            $table->text('file_seguro')->nullable();
            $table->text('file_vtv')->nullable()->after('file_seguro');
            $table->text('file_cedula')->nullable()->after('file_vtv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autos', function (Blueprint $table) {
            $table->dropColumn(['file_seguro', 'file_vtv', 'file_cedula']);
        });
    }
};
