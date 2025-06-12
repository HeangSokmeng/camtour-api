<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // breakfast, lunch, dinner, snack
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_per_person', 8, 2);
            $table->string('cuisine_type')->nullable(); // khmer, western, asian, etc
            $table->string('meal_type')->nullable(); // street_food, restaurant, hotel, etc
            $table->json('dietary_options')->nullable(); // vegetarian, vegan, halal, etc
            $table->integer('preparation_time_minutes')->default(30);
            $table->string('location_type')->nullable(); // local_market, restaurant, hotel
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
