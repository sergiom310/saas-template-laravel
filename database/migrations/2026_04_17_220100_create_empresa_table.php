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
        Schema::create('empresa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('sitio_web', 200)->nullable();
            $table->string('logo')->nullable()->comment('Ruta del logo de la empresa');
            $table->string('imagen_header')->nullable()->comment('Ruta de imagen de cabecera para reportes');
            $table->string('ciudad', 100)->nullable();
            $table->string('pais', 100)->nullable()->default('Colombia');
            $table->text('descripcion')->nullable();
            $table->string('moneda', 10)->nullable()->default('COP');
            $table->string('impuesto_label', 30)->nullable()->default('IVA');
            $table->decimal('impuesto_porcentaje', 5, 2)->nullable()->default(19.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
