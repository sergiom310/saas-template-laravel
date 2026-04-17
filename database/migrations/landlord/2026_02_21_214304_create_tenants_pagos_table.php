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
            $table->foreignId('modulo_id')->constrained('modulos')->onDelete('cascade');
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 10, 2);
            $table->enum('metodo_pago', ['mensual', 'anual']);
            $table->date('fecha_inicio_periodo');
            $table->date('fecha_fin_periodo');
            $table->string('referencia_pago', 100)->nullable()->comment('Número de transacción o referencia del pago');
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['tenant_id', 'fecha_pago']);
            $table->index(['modulo_id', 'fecha_pago']);
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
