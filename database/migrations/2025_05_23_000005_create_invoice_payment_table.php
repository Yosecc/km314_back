<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_payment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('payment_id')->constrained();
            $table->decimal('amount', 12, 2); // Monto aplicado de este pago a esta factura
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_payment');
    }
};
