<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE form_controls MODIFY status ENUM('OwnerPending','Authorized','Denied','Pending') NOT NULL");
        Schema::table('form_controls', function (Blueprint $table) {
            $table->dateTime('owner_approved_at')->nullable()->after('status');
            $table->foreignId('owner_approved_by_user_id')->nullable()->after('owner_approved_at')->constrained('users')->nullOnDelete();
        });
        Schema::create('form_control_public_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('owner_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('lote_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->foreignId('used_form_control_id')->nullable()->constrained('form_controls')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'used_at', 'revoked_at'], 'fc_public_invitation_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_control_public_invitations');
        Schema::table('form_controls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_approved_by_user_id');
            $table->dropColumn('owner_approved_at');
        });
        DB::table('form_controls')->where('status', 'OwnerPending')->update(['status'=>'Pending']);
        DB::statement("ALTER TABLE form_controls MODIFY status ENUM('Authorized','Denied','Pending') NOT NULL");
    }
};
