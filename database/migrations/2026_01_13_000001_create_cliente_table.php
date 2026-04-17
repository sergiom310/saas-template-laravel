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
        Schema::create('cliente', function (Blueprint $table) {
            $table->bigIncrements('cod_cli');
            $table->string('nom_cli');
            $table->string('telefono')->nullable();
            $table->unsignedBigInteger('tipo_vehi')->nullable();
            $table->string('placa')->nullable();
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();
            // Valor mensual que paga el cliente (nullable durante desarrollo)
            $table->decimal('valor_mensual', 10, 2)->nullable();
            $table->char('estado', 1)->default('A'); // A = Activo, I = Inactivo
            $table->char('imp', 1)->default('N'); // S = Sí, N = No
            $table->timestamps();

            // Foreign key
            $table->foreign('tipo_vehi')
                ->references('id')
                ->on('tipo_vehiculo')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
