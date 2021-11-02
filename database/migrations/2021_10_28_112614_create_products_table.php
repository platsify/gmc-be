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
            $table->id();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('original_product_id')->index('INDEX_ORIGINAL_PRODUCT_ID');
            $table->integer('original_last_update');
            $table->integer('category_id')->index('INDEX_CATEGORY_ID');
            $table->boolean('sync_gmc')->default(false)->comment('Does this product synced to GMC?');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('products');
    }
}
