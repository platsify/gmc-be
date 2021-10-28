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
            $table->string('in_stock')->default('in stock');
            $table->float('price')->default(10);
            $table->string('currency')->default('USD');
            $table->string('condition')->default('new');
            $table->string('brand')->default('WangChou');
            $table->string('mpn')->default('mpn');
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
