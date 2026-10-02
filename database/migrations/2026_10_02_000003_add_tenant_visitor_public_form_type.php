<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPE = 'Visitante de Inquilino';

    public function up(): void
    {
        Schema::table('form_control_public_invitations', function (Blueprint $table) {
            $table->string('income_type')->default('Inquilino')->after('lote_id');
        });

        $now = now();

        DB::table('form_control_type_incomes')->updateOrInsert(
            ['name' => self::TYPE],
            ['color' => '#8e24aa', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        );

        if (Schema::hasTable('files_requireds') && ! DB::table('files_requireds')->where('type', self::TYPE)->exists()) {
            $tenantDocuments = DB::table('files_requireds')
                ->whereIn('type', ['Inquilino', 'inquilino'])
                ->first();

            DB::table('files_requireds')->insert([
                'type' => self::TYPE,
                'name' => 'Documentos para visitante de inquilino',
                'required' => $tenantDocuments?->required ?? '[]',
                'no_required' => $tenantDocuments?->no_required ?? '[]',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('files_requireds')) {
            DB::table('files_requireds')->where('type', self::TYPE)->delete();
        }

        DB::table('form_control_type_incomes')->where('name', self::TYPE)->delete();

        Schema::table('form_control_public_invitations', function (Blueprint $table) {
            $table->dropColumn('income_type');
        });
    }
};
