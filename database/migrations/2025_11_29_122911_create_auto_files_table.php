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
        Schema::create('auto_files', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('file');
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->unsignedBigInteger('auto_id');
            $table->foreign('auto_id')->references('id')->on('autos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('autos', function (Blueprint $table) {
            $table->dropColumn(['file_seguro', 'file_vtv', 'file_cedula']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_files');
    }
};
