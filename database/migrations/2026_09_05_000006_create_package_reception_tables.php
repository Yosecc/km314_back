<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_receptions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->foreignId('owner_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('lote_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('courier_name');
            $table->dateTime('expected_from');
            $table->dateTime('expected_until');
            $table->text('carrier_access_code')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_dni')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->unsignedSmallInteger('expected_packages_count')->nullable();
            $table->unsignedSmallInteger('received_packages_count')->nullable();
            $table->text('observations')->nullable();
            $table->string('status')->default('expected');
            $table->dateTime('received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reception_notes')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->foreignId('delivered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delivered_to_type')->nullable();
            $table->string('delivered_to_name')->nullable();
            $table->string('delivered_to_dni')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->dateTime('overdue_notified_at')->nullable();
            $table->dateTime('pickup_reminder_sent_at')->nullable();
            $table->dateTime('pickup_critical_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expected_until']);
            $table->index(['owner_id', 'status']);
            $table->index(['lote_id', 'status']);
        });

        Schema::create('package_reception_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_reception_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['package_reception_id', 'category']);
        });

        Schema::create('package_reception_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_reception_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['package_reception_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_reception_events');
        Schema::dropIfExists('package_reception_files');
        Schema::dropIfExists('package_receptions');
    }
};
