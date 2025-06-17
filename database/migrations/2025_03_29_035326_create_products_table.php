<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->string('name_km')->nullable();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->enum('status', ['drafting', 'published'])->default('drafting');
            $table->string('thumbnail')->nullable();
            $table->decimal('total_views')->default(0);

            $table->foreign('product_category_id')->references('id')->on('product_categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');

    }
};
