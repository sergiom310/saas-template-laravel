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
        Schema::table('agd_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('user_id')->comment('Cliente asociado a la venta (opcional)');
            $table->foreign('cliente_id')->references('id_cliente')->on('agd_cliente')->onDelete('set null');
            $table->index('cliente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agd_ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropIndex(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
};
