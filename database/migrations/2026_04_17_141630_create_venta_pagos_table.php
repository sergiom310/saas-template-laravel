<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id')->comment('ID de la venta');
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->unsignedTinyInteger('metodo_pago_id')->comment('ID del método de pago');
            $table->foreign('metodo_pago_id')->references('id')->on('metodo_pago')->onDelete('restrict');
            $table->decimal('monto', 19, 2)->comment('Monto pagado');
            $table->string('referencia', 100)->nullable()->comment('Referencia de pago (transacción, cheque, etc.)');
            $table->text('observaciones')->nullable()->comment('Notas u observaciones del pago');
            $table->timestamp('fecha_pago')->useCurrent()->comment('Fecha y hora del pago');
            $table->timestamps();

            $table->index('venta_id');
            $table->index('metodo_pago_id');
            $table->index('fecha_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
