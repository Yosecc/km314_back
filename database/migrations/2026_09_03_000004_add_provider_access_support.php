<?php

use App\Models\Proveedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('quick_access_code', 12)->nullable()->unique()->after('id');
        });

        Proveedor::query()->whereNull('quick_access_code')->each(function (Proveedor $proveedor): void {
            $proveedor->update(['quick_access_code' => Proveedor::generateUniqueCode()]);
        });

        Schema::table('form_controls', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->after('owner_id')
                ->constrained('proveedores')->nullOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->after('form_control_id')
                ->constrained('proveedores')->nullOnDelete();
        });

        Schema::create('activity_form_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('form_control_id')->constrained('form_controls')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['activity_id', 'form_control_id']);
        });

        DB::table('form_control_type_incomes')->updateOrInsert(
            ['name' => 'Proveedor'],
            ['status' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('form_control_type_incomes')->where('name', 'Proveedor')->delete();
        Schema::dropIfExists('activity_form_control');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
        });

        Schema::table('form_controls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropUnique(['quick_access_code']);
            $table->dropColumn('quick_access_code');
        });
    }
};
