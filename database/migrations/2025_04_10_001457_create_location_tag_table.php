<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_tag', function (Blueprint $table) {
            // setup column
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            // setup relationship
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_tag');
    }
};
