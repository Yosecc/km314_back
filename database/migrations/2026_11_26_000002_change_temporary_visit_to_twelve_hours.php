<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PREVIOUS_TYPE = 'Visita Temporal (24hs)';
    private const CURRENT_TYPE = 'Visita Temporal (12hs)';

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->renameType(self::PREVIOUS_TYPE, self::CURRENT_TYPE);

            if (! DB::table('form_control_type_incomes')->where('name', self::CURRENT_TYPE)->exists()) {
                DB::table('form_control_type_incomes')->insert([
                    'name' => self::CURRENT_TYPE,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(fn () => $this->renameType(self::CURRENT_TYPE, self::PREVIOUS_TYPE));
    }

    private function renameType(string $from, string $to): void
    {
        DB::table('form_control_type_incomes')->where('name', $from)->update(['name' => $to]);
        DB::table('files_requireds')->where('type', $from)->update(['type' => $to]);
    }
};
