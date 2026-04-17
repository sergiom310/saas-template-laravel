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
        Schema::create('agd_venta_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id')->comment('ID de la venta');
            $table->foreign('venta_id')->references('id')->on('agd_ventas')->onDelete('cascade');
            $table->unsignedBigInteger('producto_id')->comment('ID del producto vendido');
            $table->foreign('producto_id')->references('id')->on('products')->onDelete('restrict');
            $table->string('producto_nombre')->comment('Nombre del producto al momento de la venta');
            $table->integer('cantidad')->default(1)->comment('Cantidad de productos vendidos');
            $table->decimal('precio_unitario', 19, 2)->comment('Precio unitario al momento de la venta');
            $table->decimal('subtotal', 19, 2)->comment('Subtotal (cantidad * precio_unitario)');
            $table->decimal('descuento', 19, 2)->default(0)->comment('Descuento aplicado a este ítem');
            $table->decimal('total', 19, 2)->comment('Total del ítem (subtotal - descuento)');
            $table->timestamps();
            
            $table->index('venta_id');
            $table->index('producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agd_venta_detalle');
    }
};
