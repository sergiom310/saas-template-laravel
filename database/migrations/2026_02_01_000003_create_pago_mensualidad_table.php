<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagoMensualidadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pago_mensualidad', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id_pago');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_cuadre');
            $table->unsignedTinyInteger('metodo_pago_id');
            $table->string('periodo', 7);
            $table->timestamp('fecha_pago')->useCurrent();
            $table->decimal('valor', 12, 2);
            $table->string('usuario', 50);

            $table->foreign('id_cuadre', 'fk_pm_cuadre')
                ->references('id_cuadre')->on('cuadre_caja')
                ->onDelete('cascade');

            $table->foreign('metodo_pago_id', 'fk_pm_metodo')
                ->references('id')->on('metodo_pago')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pago_mensualidad');
    }
}
