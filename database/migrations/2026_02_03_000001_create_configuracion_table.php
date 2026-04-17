<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->char('imp_logo', 1)->default('S');
            $table->char('imp_mensaje', 1)->default('S');
            $table->char('imp_nit', 1)->default('S');
            $table->char('imp_tel', 1)->default('S');
            $table->char('imp_dir', 1)->default('S');
            $table->char('imp_nombre', 1)->default('S');
            $table->char('imp_horario', 1)->default('S');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('configuracion');
    }
};
