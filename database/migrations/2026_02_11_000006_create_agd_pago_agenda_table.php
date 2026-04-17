<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agd_pago_agenda', function (Blueprint $table) {
            $table->id('id_pago');
            $table->unsignedBigInteger('id_agenda');
            $table->decimal('monto', 10, 2);
            $table->unsignedTinyInteger('metodo_pago');
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->dateTime('fecha_pago')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->foreign('id_agenda')->references('id_agenda')->on('agd_agenda');
            $table->foreign('metodo_pago')->references('id')->on('agd_metodo_pago')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agd_pago_agenda');
    }
};
