<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductMapCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_map_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->index('INDEX_PRODUCT_ID');
            $table->integer('category_id')->index('INDEX_CATEGORY_ID');
            $table->string('pici')->index('INDEX_PICI');
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
        Schema::dropIfExists('product_map_categories');
    }
}
