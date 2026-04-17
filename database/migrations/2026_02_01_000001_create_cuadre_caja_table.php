<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCuadreCajaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuadre_caja', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id_cuadre');
            $table->string('usuario', 50);
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('total_ingresos', 12, 2)->default(0);
            $table->decimal('base', 12, 2)->default(0)->comment('Base inicial al abrir la caja');
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            // Índice no único para consultas por usuario y estado (evita conflicto único al cerrar)
            $table->index(['usuario', 'estado'], 'idx_cuadre_usuario_estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cuadre_caja');
    }
}
