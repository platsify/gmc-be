<?php

use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('public_url');
            $table->integer('type')->default(1)->comment('1 is ApiShopbase, 2 is Woocomerce');
            $table->string('gmc_id');
            $table->string('gmc_credential');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sync_status')->default(Shop::SHOP_SYNC_NEVER);
            $table->integer('total_product')->default(0);
            $table->integer('crawled_product')->default(0);
            $table->integer('last_sync')->default(0);
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
        Schema::dropIfExists('shops');
    }
}
