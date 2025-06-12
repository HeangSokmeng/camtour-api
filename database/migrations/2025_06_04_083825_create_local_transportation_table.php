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
        Schema::create('local_transportations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // motorbike, tuk_tuk, tricycle
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_per_hour', 8, 2)->nullable();
            $table->decimal('price_per_day', 8, 2)->nullable();
            $table->decimal('price_per_trip', 8, 2)->nullable();
            $table->decimal('estimated_daily_cost', 8, 2)->nullable();
            $table->integer('capacity_people')->default(2);
            $table->json('suitable_for')->nullable(); // age groups, trip types
            $table->json('advantages')->nullable();
            $table->json('disadvantages')->nullable();
            $table->string('booking_method')->nullable(); // street, hotel, app
            $table->boolean('driver_included')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_transportation');
    }
};
