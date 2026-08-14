<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla puede existir si una ejecución anterior falló al ampliar el ENUM de autos.
        if (!Schema::hasTable('recurrent_visitors')) {
            Schema::create('recurrent_visitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
                $table->string('dni');
                $table->string('first_name');
                $table->string('last_name');
                $table->string('phone')->nullable();
                $table->text('identity_document')->nullable();
                $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
                $table->text('observations')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Los autos existentes son polimorficos por el campo model.
        // OwnerFamily ya existe en instalaciones antiguas: conservarlo evita truncar esos registros.
        DB::statement("ALTER TABLE autos MODIFY model ENUM('Employee', 'Owner', 'FormControl', 'OwnerFamily', 'RecurrentVisitor') NOT NULL");

        DB::table('form_control_type_incomes')->updateOrInsert(
            ['name' => 'Visita Recurrente'],
            ['status' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('form_control_type_incomes')->where('name', 'Visita Recurrente')->delete();
        DB::statement("ALTER TABLE autos MODIFY model ENUM('Employee', 'Owner', 'FormControl', 'OwnerFamily') NOT NULL");
        Schema::dropIfExists('recurrent_visitors');
    }
};
