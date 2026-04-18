<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('modulo_id')->nullable();
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago', 100)->nullable()->comment('Forma de pago: Efectivo, Transferencia, etc.');
            $table->enum('tipo_periodo', ['mensual', 'anual'])->nullable()->comment('Duración del período contratado');
            $table->date('fecha_inicio_periodo');
            $table->date('fecha_fin_periodo')->nullable();
            $table->string('referencia_pago', 100)->nullable()->comment('Número de transacción o referencia del pago');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['tenant_id', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants_pagos');
    }
};
