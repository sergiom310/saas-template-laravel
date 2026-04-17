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
        Schema::create('agd_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Usuario que realiza la venta');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->decimal('subtotal', 19, 2)->default(0)->comment('Subtotal de la venta');
            $table->decimal('descuento', 19, 2)->default(0)->comment('Descuento aplicado');
            $table->decimal('total', 19, 2)->default(0)->comment('Total de la venta');
            $table->string('estado', 50)->default('pendiente')->comment('Estado: pendiente, pagada, cancelada');
            $table->text('observaciones')->nullable()->comment('Notas u observaciones de la venta');
            $table->timestamp('fecha_venta')->useCurrent()->comment('Fecha y hora de la venta');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('estado');
            $table->index('fecha_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agd_ventas');
    }
};
