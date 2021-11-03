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
            $table->string('name', 500);
            $table->string('url', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('original_id')->index('INDEX_ORIGINAL_ID');
            $table->integer('original_last_update');
            $table->integer('shop_id')->index('INDEX_SHOP_ID');
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
