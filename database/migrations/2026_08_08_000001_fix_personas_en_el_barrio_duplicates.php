<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(base_path('view_personas_en_el_barrio.sql')));
    }

    public function down(): void
    {
    }
};
