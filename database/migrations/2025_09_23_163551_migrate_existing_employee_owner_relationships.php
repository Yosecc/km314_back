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
        DB::statement("
            INSERT INTO employee_owner (employee_id, owner_id, created_at, updated_at)
            SELECT id, owner_id, created_at, updated_at 
            FROM employees 
            WHERE owner_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         DB::table('employee_owner')->truncate();
    }
};
