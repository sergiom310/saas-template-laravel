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
        Schema::create('pago', function (Blueprint $table) {
            $table->id('id_pago')->comment('ID del pago');
            $table->unsignedBigInteger('cod_cli')->comment('Código del cliente');
            $table->decimal('val_pag', 17, 0)->nullable()->comment('Valor del pago');
            $table->date('desde')->nullable()->comment('Fecha desde');
            $table->date('hasta')->nullable()->comment('Fecha hasta');
            $table->date('fecha_pag')->nullable()->comment('Fecha del pago');
            $table->string('horap', 5)->nullable()->comment('Hora del pago');
            $table->string('user_sys', 150)->nullable()->comment('Usuario del sistema');
            $table->unsignedTinyInteger('cod_forp')->nullable()->default(1)->comment('Código de forma de pago');
            $table->timestamps();

            // Relaciones
            $table->foreign('cod_cli')->references('cod_cli')->on('cliente')->onDelete('cascade');
            $table->foreign('cod_forp')->references('id')->on('metodo_pago')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
