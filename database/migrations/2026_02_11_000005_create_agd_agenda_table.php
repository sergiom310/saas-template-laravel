<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agd_agenda', function (Blueprint $table) {
            $table->id('id_agenda');
            $table->date('fecha');
            $table->unsignedBigInteger('id_franja');
            $table->unsignedBigInteger('id_profesional');
            $table->unsignedBigInteger('id_cliente');
            $table->text('procedimiento')->nullable();
            $table->enum('estado', ['reservado', 'atendido', 'cancelado'])->default('reservado');
            $table->dateTime('fecha_creacion')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->foreign('id_franja')->references('id_franja')->on('agd_franja_horaria');
            $table->foreign('id_profesional')->references('id_profesional')->on('agd_profesional');
            $table->foreign('id_cliente')->references('id_cliente')->on('agd_cliente');
            $table->unique(['fecha', 'id_franja', 'id_profesional'], 'uk_agenda_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agd_agenda');
    }
};
