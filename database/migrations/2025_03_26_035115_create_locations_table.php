<?php

use App\Traits\BaseMigrationField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use BaseMigrationField;

    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            // setup column
            $this->AddBaseFields($table);

            $table->string('name')->nullable();
            $table->string('name_local')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('url_location')->nullable();
            $table->integer('total_view')->default(0);
            $table->decimal('lat', 10, 7)->default(0);
            $table->decimal('lot', 10, 7)->default(0);
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->unsignedTinyInteger('category_id')->nullable();
            $table->timestamp('published_at')->nullable();

            // setup relationship
            $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('commune_id')->references('id')->on('communes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('village_id')->references('id')->on('villages')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
