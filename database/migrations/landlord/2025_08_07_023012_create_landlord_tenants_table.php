<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_company')->unique();
            $table->string('description')->nullable();
            $table->string('domain')->unique();
            $table->string('database')->unique();
            $table->string('owner_email')->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamp('migrated_at')->nullable();
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('estado_pago')->default(false);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
