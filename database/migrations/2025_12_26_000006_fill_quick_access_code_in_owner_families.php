<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $families = \App\Models\OwnerFamily::whereNull('quick_access_code')->get();
        foreach ($families as $family) {
            $family->quick_access_code = \App\Models\OwnerFamily::generateUniqueCode();
            $family->save();
        }
    }

    public function down(): void
    {
        // Opcional: puedes dejarlo vacío
    }
};
