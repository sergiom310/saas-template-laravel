<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('status', ['Pendiente', 'Procesando', 'Completado', 'Cancelado'])->nullable();
            $table->string('status_payment', 20)->nullable();
            $table->timestamp('date_payment')->nullable();
            $table->string('status_delivery')->nullable();
            $table->timestamp('date_delivery')->nullable();
            $table->decimal('total_payment',19, 2)->nullable()->default(0);
            $table->decimal('balance_payment', 19, 2)->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
