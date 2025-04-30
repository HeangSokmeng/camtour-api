<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table) {
            // setup columns
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->string('code');

            // setup relationship
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colors');
    }
};
