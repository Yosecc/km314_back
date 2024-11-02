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
        Schema::create('landings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('btnactioname')->nullable();
            $table->string('btnactiomessage')->nullable();
            $table->string('content')->nullable();
            $table->boolean('status');
            $table->timestamps();
        });
    }
// ALTER TABLE `landings` ADD `content` TEXT NULL AFTER `updated_at`; 
//<h2>contenidod fgddfsfgdsdfg</h2><p>sdfgl;'kgdfssdfg</p><p>dfsoldfgsldfgs</p><p>sdfgll;sdf</p>
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landings');
    }
};
