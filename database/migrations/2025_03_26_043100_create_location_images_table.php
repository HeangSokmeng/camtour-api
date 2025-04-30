<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_images', function (Blueprint $table) {
            // setup column
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->string('photo')->nullable();
            $table->timestamps();

            // setup relationship
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_images');
    }
};
