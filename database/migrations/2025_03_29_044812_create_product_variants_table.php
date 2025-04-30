<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            // setup column
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_color_id')->nullable();
            $table->unsignedBigInteger('product_size_id');
            $table->integer('qty')->default(0);
            $table->decimal('price', 15, 2)->default(0);

            // setup relationship
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('product_color_id')->references('id')->on('product_colors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('product_size_id')->references('id')->on('product_sizes')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
