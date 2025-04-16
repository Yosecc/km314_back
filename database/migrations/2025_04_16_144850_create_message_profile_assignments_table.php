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
        Schema::create('message_profile_assignments', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(false); // Estado de la asignación
            $table->string('type');
            $table->string('message_id'); // ID del mensaje (de ConversationsMail)
            $table->unsignedBigInteger('user_id'); // ID del perfil
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_profile_assignments');
    }
};
