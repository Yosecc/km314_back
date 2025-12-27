<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Employees
        $employees = \App\Models\Employee::whereNull('quick_access_code')->get();
        foreach ($employees as $employee) {
            $employee->quick_access_code = \App\Models\Employee::generateUniqueCode();
            $employee->save();
        }

        // Owners
        $owners = \App\Models\Owner::whereNull('quick_access_code')->get();
        foreach ($owners as $owner) {
            $owner->quick_access_code = \App\Models\Owner::generateUniqueCode();
            $owner->save();
        }

        // FormControls
        $forms = \App\Models\FormControl::whereNull('quick_access_code')->get();
        foreach ($forms as $form) {
            $form->quick_access_code = \App\Models\FormControl::generateUniqueCode();
            $form->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opcional: puedes dejarlo vacío o limpiar los códigos
    }
};
