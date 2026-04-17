<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagoParqueaderoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pago_parqueadero', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id_pago');
            $table->unsignedInteger('id_factura');
            $table->unsignedInteger('id_cuadre');
            $table->unsignedTinyInteger('metodo_pago_id');
            $table->timestamp('fecha_pago')->useCurrent();
            $table->decimal('valor', 12, 2);
            $table->string('usuario', 50);

            $table->foreign('id_cuadre', 'fk_pp_cuadre')
                ->references('id_cuadre')->on('cuadre_caja')
                ->onDelete('cascade');

            $table->foreign('metodo_pago_id', 'fk_pp_metodo')
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
        Schema::dropIfExists('pago_parqueadero');
    }
}
