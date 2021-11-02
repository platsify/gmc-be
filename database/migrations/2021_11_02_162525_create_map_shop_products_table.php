<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapShopProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_shop_products', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->index('INDEX_PRODUCT_ID');
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
        Schema::dropIfExists('map_shop_products');
    }
}
