<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            // setup columns
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('product_id');
            $table->string('size');

            // setup relationship
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};
