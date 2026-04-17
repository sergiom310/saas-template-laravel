<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->mediumText('description')->nullable();
            $table->string('name_en')->nullable();
            $table->mediumText('description_en')->nullable();
            $table->double('cost',19,2)->nullable()->default(0);
            $table->double('cost_usd',19,2)->nullable()->default(0);
            $table->double('price',19,2)->nullable()->default(0);
            $table->double('price_usd',19,2)->nullable()->default(0);
            $table->double('minimum',19,2)->nullable()->default(0);
            $table->string('status')->default('Activo');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->unsignedInteger('stock')->nullable();
            $table->string('sku', 100)->unique()->nullable();
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('cover_img')->nullable();
            $table->double('alcohol_percentage', 5,2)->nullable()->default(0);
            $table->timestamp('expiry_date')->nullable();
            $table->string('size', 50)->nullable();
            $table->double('weight', 8,2)->nullable()->default(0);
            $table->string('unit_type', 50)->nullable();
            $table->boolean('show_price')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('stock_visible')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('discount_active')->default(false);
            $table->integer('discount_percent')->nullable();
            $table->integer('min_order_qty')->default(1);
            $table->integer('max_order_qty')->nullable();
            $table->boolean('show_related')->default(true);
            $table->timestamps();
            $table->fullText(['name', 'description']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
