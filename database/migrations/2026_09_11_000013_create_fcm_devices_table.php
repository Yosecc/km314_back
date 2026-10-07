<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Un token FCM es ASCII. Así el índice único es compatible con
            // MySQL aun usando utf8mb4 para el resto de la base.
            $table->string('token', 1024)->charset('ascii')->collation('ascii_bin')->unique();
            $table->string('platform', 20);
            $table->string('device_name')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_devices');
    }
};
