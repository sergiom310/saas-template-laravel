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
        Schema::create('tarifa_regla', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('tarifa_id');
            $table->unsignedBigInteger('tipo_vehiculo_id');
            
            // Rango de minutos
            $table->integer('minutos_desde');
            $table->integer('minutos_hasta');
            
            // Contexto de aplicación
            $table->enum('contexto', ['TOTAL', 'FRACCION']);
            
            // Tipo de cálculo
            $table->enum('tipo_calculo', ['FIJO', 'POR_HORA', 'COBRO_LIBRE']);
            
            // Valor
            $table->decimal('valor', 10, 0);
            
            // Prioridad
            $table->integer('prioridad')->default(1);
            
            // Foreign key constraints
            $table->foreign('tarifa_id')->references('id')->on('tarifa');
            $table->foreign('tipo_vehiculo_id')->references('id')->on('tipo_vehiculo');
            
            // Índice compuesto para búsquedas
            $table->index(['tarifa_id', 'tipo_vehiculo_id', 'contexto', 'prioridad'], 'idx_busqueda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifa_regla');
    }
};
