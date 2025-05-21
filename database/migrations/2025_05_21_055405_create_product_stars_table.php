<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('product_stars', function (Blueprint $table) {
            // setup column
            $this->AddBaseFields($table);
            $table->unsignedBigInteger('rater_id');
            $table->unsignedBigInteger('product_id');
            $table->string('comment')->nullable();
            $table->unsignedTinyInteger('star')->nullable();

            // setup relationship
            $table->foreign('rater_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stars');
    }
};
