<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCuadreDetalleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuadre_detalle', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('id_cuadre');
            $table->unsignedTinyInteger('metodo_pago_id');
            $table->decimal('total', 12, 2);

            $table->foreign('id_cuadre', 'fk_cd_cuadre')
                ->references('id_cuadre')->on('cuadre_caja')
                ->onDelete('cascade');

            $table->foreign('metodo_pago_id', 'fk_cd_metodo')
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
        Schema::dropIfExists('cuadre_detalle');
    }
}
