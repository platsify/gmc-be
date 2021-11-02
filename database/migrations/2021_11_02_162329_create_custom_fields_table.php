<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('object_type')->default('1')->comment('1 là product, 2 là project');
            $table->integer('object_id')->index('INDEX_OBJECT_ID');
            $table->string('name')->index('INDEX_NAME');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['object_type', 'object_id'], 'INDEX_TYPE_AND_ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
