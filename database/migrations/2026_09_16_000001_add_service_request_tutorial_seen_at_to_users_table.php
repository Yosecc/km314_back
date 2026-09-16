<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'service_request_tutorial_seen_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('service_request_tutorial_seen_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'service_request_tutorial_seen_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('service_request_tutorial_seen_at');
            });
        }
    }
};
