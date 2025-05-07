<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    use BaseMigrationField;

    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->integer('qty');
            $table->decimal('price', 10, 2);

            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');
            $table->foreign('color_id')->references('id')->on('product_colors')->onDelete('set null');
            $table->foreign('size_id')->references('id')->on('product_sizes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
}
