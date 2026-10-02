<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employee_tutorial_seen_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('employee_tutorial_seen_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'employee_tutorial_seen_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('employee_tutorial_seen_at');
            });
        }
    }
};
