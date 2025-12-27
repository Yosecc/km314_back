<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_families', function (Blueprint $table) {
            $table->string('quick_access_code', 12)->unique()->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('owner_families', function (Blueprint $table) {
            $table->dropColumn('quick_access_code');
        });
    }
};
