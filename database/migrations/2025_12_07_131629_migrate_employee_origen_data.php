<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        DB::statement("
            INSERT INTO employee_origens (employee_id, model, model_id, created_at, updated_at)
            SELECT 
                id as employee_id,
                model_origen as model,
                model_origen_id as model_id,
                NOW() as created_at,
                NOW() as updated_at
            FROM employees
            WHERE model_origen IS NOT NULL 
              AND model_origen != ''
              AND model_origen_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Eliminar los datos migrados si es necesario hacer rollback
        DB::table('employee_origens')->truncate();
    }
};
