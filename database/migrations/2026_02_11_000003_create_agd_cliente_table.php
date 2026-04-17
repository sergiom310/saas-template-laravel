<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agd_cliente', function (Blueprint $table) {
            $table->id('id_cliente');
            $table->string('cedula', 20)->nullable();
            $table->string('nombre', 100);
            $table->string('telefono', 20);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agd_cliente');
    }
};
