<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interested_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('interesteds', function (Blueprint $table) {
            $table->foreignId('interested_type_id')
                ->nullable()
                ->after('interested_origins_id')
                ->constrained('interested_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('interesteds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interested_type_id');
        });

        Schema::dropIfExists('interested_types');
    }
};
