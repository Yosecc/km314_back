<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_invoiced', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->foreignId('last_invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('last_payment_id')->nullable()->constrained('payments');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_statuses');
    }
};
