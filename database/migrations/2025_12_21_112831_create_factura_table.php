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
        Schema::create('factura', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('tarifa_id')->nullable();
            $table->unsignedBigInteger('tipo_vehiculo_id');

            // Datos del vehículo
            $table->string('placa', 20);

            // Fechas
            $table->dateTime('fecha_entrada');
            $table->dateTime('fecha_salida')->nullable();

            // Cálculos
            $table->integer('minutos_total')->nullable();

            // Reglas aplicadas
            $table->unsignedBigInteger('regla_total_id')->nullable();
            $table->unsignedBigInteger('regla_fraccion_id')->nullable();

            // Valores
            $table->decimal('valor_calculado', 10, 0)->nullable();
            $table->decimal('valor_manual', 10, 0)->nullable();
            $table->decimal('valor_pagado', 10, 0)->nullable();
            $table->decimal('pendiente', 10, 0)->nullable();

            // Método de pago
            $table->unsignedTinyInteger('metodo_pago_id')->nullable();

            // Estado
            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');

            // Usuarios
            $table->unsignedBigInteger('user_created');
            $table->unsignedBigInteger('user_updated')->nullable();

            // Timestamps
            $table->timestamps();

            // Campos adicionales
            $table->text('observacion')->nullable();
            $table->string('detalle', 255)->nullable();
            $table->char('queda', 1)->default('N');
            $table->char('pendiente_flag', 1)->default('N');
            $table->decimal('val_pago1', 10, 0)->default(0);
            $table->char('multa', 1)->default('N');
            $table->char('pleno', 1)->default('N');

            // Foreign key constraints
            $table->foreign('tarifa_id')->references('id')->on('tarifa')->nullOnDelete();
            $table->foreign('tipo_vehiculo_id')->references('id')->on('tipo_vehiculo');
            $table->foreign('regla_total_id')->references('id')->on('tarifa_regla')->nullOnDelete();
            $table->foreign('regla_fraccion_id')->references('id')->on('tarifa_regla')->nullOnDelete();
            $table->foreign('metodo_pago_id')->references('id')->on('metodo_pago')->nullOnDelete();
            $table->foreign('user_created')->references('id')->on('users');
            $table->foreign('user_updated')->references('id')->on('users');

            // Índices
            $table->index('estado');
            $table->index('fecha_entrada');
            $table->index('placa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura');
    }
};
