<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agd_profesional', function (Blueprint $table) {
            $table->id('id_profesional');
            $table->string('nombre', 100);
            $table->unsignedBigInteger('id_especialidad');
            $table->string('telefono', 20)->nullable();
            $table->boolean('activo')->default(1);
            $table->timestamps();

            $table->foreign('id_especialidad')
                ->references('id_especialidad')
                ->on('agd_especialidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agd_profesional');
    }
};
